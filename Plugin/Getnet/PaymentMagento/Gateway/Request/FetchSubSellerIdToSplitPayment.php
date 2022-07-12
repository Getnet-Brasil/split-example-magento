<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\SplitExampleMagento\Plugin\Getnet\PaymentMagento\Gateway\Request;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use Getnet\PaymentMagento\Gateway\Request\SplitPaymentDataRequest;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use Getnet\SplitExampleMagento\Helper\Data as SplitHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class Fetch Sub Seller Id To Split Payment - add Sub Seller in Transaction.
 */
class FetchSubSellerIdToSplitPayment
{
    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var configCc
     */
    protected $configCc;

    /**
     * @var SplitHelper;
     */
    protected $splitHelper;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @param SubjectReader        $subjectReader
     * @param OrderAdapterFactory  $orderAdapterFactory
     * @param Config               $config
     * @param ConfigCc             $configCc
     * @param SplitHelper          $splitHelper
     * @param Json                 $json
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        Config $config,
        ConfigCc $configCc,
        SplitHelper $splitHelper,
        Json $json,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->config = $config;
        $this->configCc = $configCc;
        $this->splitHelper = $splitHelper;
        $this->json = $json;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Around method Build.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param SplitPaymentDataRequest $subject
     * @param \Closure                $proceed
     * @param array                   $buildSubject
     *
     * @return mixin
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function aroundBuild(
        SplitPaymentDataRequest $subject,
        \Closure $proceed,
        $buildSubject
    ) {
        $result = $proceed($buildSubject);

        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();

        $result = [];

        $marketplace = [];

        $installment = 0;

        /** @var OrderAdapterFactory $orderAdapter * */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $order = $paymentDO->getOrder();

        $dataSellers = $this->getDataForSplit($order);

        $shippingAmount = $orderAdapter->getShippingAmount();

        $payment = $paymentDO->getPayment();

        $installment = $payment->getAdditionalInformation('cc_installments') ?: 1;

        if ($payment->getMethod() === 'getnet_paymentmagento_boleto'
            || $payment->getMethod() === 'getnet_paymentmagento_pix'
            || $payment->getMethod() === 'getnet_paymentmagento_getpay'
        ) {
            $installment = 0;
        }

        $storeId = $order->getStoreId();

        if (!isset($dataSellers['productBySeller'])) {
            return $result;
        }

        foreach ($dataSellers['productBySeller'] as $sellerId => $products) {
            $priceShippingBySeller = 0;
            $productAmount = array_sum(array_column($dataSellers['pricesBySeller'][$sellerId], 'totalAmount'));

            $shippingProduct = $this->addSplitShippingInSellerData($order, $shippingAmount, $sellerId, $dataSellers);

            $products['product'][] = $shippingProduct['products'][$sellerId];

            $priceShippingBySeller = $shippingProduct['amount'][$sellerId];

            $commissionAmount = $productAmount + $priceShippingBySeller;

            if ($installment > 1) {
                $interestData = $this->addSplitInterestInSellerData(
                    $sellerId,
                    $dataSellers,
                    $installment,
                    $commissionAmount,
                    $storeId
                );

                if ($interestData['amount'][$sellerId] > 0) {
                    $products['product'][] = $interestData['products'][$sellerId];

                    $commissionAmount = $commissionAmount + $interestData['amount'][$sellerId];
                }
            }

            $result[SplitPaymentDataRequest::BLOCK_NAME_MARKETPLACE_SUBSELLER_PAYMENTS][] = [
                SplitPaymentDataRequest::BLOCK_NAME_SUB_SELLER_ID          => $sellerId,
                SplitPaymentDataRequest::BLOCK_NAME_SUBSELLER_SALES_AMOUNT => $this->config->formatPrice($commissionAmount),
                SplitPaymentDataRequest::BLOCK_NAME_ORDER_ITEMS            => $products['product'],
            ];
        }

        foreach ($result[SplitPaymentDataRequest::BLOCK_NAME_MARKETPLACE_SUBSELLER_PAYMENTS] as $sellers) {
            $seller = $sellers[SplitPaymentDataRequest::BLOCK_NAME_SUB_SELLER_ID];
            $marketplace[$seller] = $sellers[SplitPaymentDataRequest::BLOCK_NAME_ORDER_ITEMS];
        }

        $payment->setAdditionalInformation(
            'marketplace',
            $this->json->serialize($marketplace)
        );

        return $result;
    }

    /**
     * Get Data for Split.
     *
     * @param OrderAdapterFactory $order
     *
     * @return array
     */
    public function getDataForSplit(
        $order
    ): array {
        $data = [];

        $storeId = $order->getStoreId();

        $items = $order->getItems();

        $qtyOrderedInOrder = 0;

        foreach ($items as $item) {

            // If product is configurable not apply
            if ($item->getParentItem()) {
                continue;
            }

            if (!$item->getProduct()->getGetnetSubSellerId()) {
                continue;
            }

            $sellerId = $item->getProduct()->getGetnetSubSellerId();
            $price = $item->getPrice() * $item->getQtyOrdered();

            $rulesToSplit = $this->splitHelper->getSplitCommissionsBySubSellerId($sellerId, $storeId);
            $commissionPercentage = $rulesToSplit['commission_percentage'] / 100;
            $commissionPerProduct = $price * $commissionPercentage;

            $data['productBySeller'][$sellerId]['product'][] = [
                SplitPaymentDataRequest::BLOCK_NAME_AMOUNT      => $this->config->formatPrice($price),
                SplitPaymentDataRequest::BLOCK_NAME_CURRENCY    => $order->getCurrencyCode(),
                SplitPaymentDataRequest::BLOCK_NAME_ID          => $item->getSku(),
                SplitPaymentDataRequest::BLOCK_NAME_DESCRIPTION => __(
                    'Product Name: %1 | Qty: %2',
                    $item->getName(),
                    $item->getQtyOrdered()
                ),
                SplitPaymentDataRequest::BLOCK_NAME_TAX_AMOUNT => $this->config->formatPrice(
                    $price - $commissionPerProduct
                ),
            ];

            $data['pricesBySeller'][$sellerId][] = [
                'totalAmount'     => $price,
                'qty'             => $item->getQtyOrdered(),
            ];

            $data['subSellerSettings'][$sellerId] = [
                'commission' => $rulesToSplit,
            ];

            $qtyOrderedInOrder = $qtyOrderedInOrder + $item->getQtyOrdered();
        }

        $data['qtyOrderedInOrder'] = $qtyOrderedInOrder;

        return $data;
    }

    /**
     * Add Split Shipping in Seller Data.
     *
     * @param OrderAdapterFactory $order
     * @param float               $shippingAmount
     * @param string              $sellerId
     * @param array               $dataSellers
     *
     * @return array
     */
    public function addSplitShippingInSellerData(
        $order,
        $shippingAmount,
        $sellerId,
        $dataSellers
    ): array {
        $shippingProduct = [];

        $qtyOrderedBySeller = array_sum(array_column($dataSellers['pricesBySeller'][$sellerId], 'qty'));

        $priceShippingBySeller = ($shippingAmount / $dataSellers['qtyOrderedInOrder']) * $qtyOrderedBySeller;

        $rule = $dataSellers['subSellerSettings'][$sellerId]['commission'];

        $shippingProduct['products'][$sellerId] = [
            SplitPaymentDataRequest::BLOCK_NAME_AMOUNT      => $this->config->formatPrice($priceShippingBySeller),
            SplitPaymentDataRequest::BLOCK_NAME_CURRENCY    => $order->getCurrencyCode(),
            SplitPaymentDataRequest::BLOCK_NAME_ID          => __('shipping-order-%1', $order->getOrderIncrementId()),
            SplitPaymentDataRequest::BLOCK_NAME_DESCRIPTION => __('Shipping for %1 products', $qtyOrderedBySeller),
            SplitPaymentDataRequest::BLOCK_NAME_TAX_AMOUNT  => ($rule['include_freight']) ? 0 : $this->config->formatPrice($priceShippingBySeller),
        ];

        $shippingProduct['amount'][$sellerId] = $priceShippingBySeller;

        return $shippingProduct;
    }

    /**
     * Add Split Interest In Seller Data.
     *
     * @param string   $sellerId
     * @param array    $dataSellers
     * @param int      $installment
     * @param float    $commissionAmount
     * @param int|null $storeId
     *
     * @return array
     */
    public function addSplitInterestInSellerData(
        string $sellerId,
        array $dataSellers,
        int $installment,
        float $commissionAmount,
        int $storeId = null
    ): array {
        $rule = $dataSellers['subSellerSettings'][$sellerId]['commission'];

        $amountInterest = $this->configCc->getInterestToAmount($installment, $commissionAmount, $storeId);

        $amountInterestProduct['products'][$sellerId] = [
            SplitPaymentDataRequest::BLOCK_NAME_AMOUNT      => $this->config->formatPrice($amountInterest),
            SplitPaymentDataRequest::BLOCK_NAME_CURRENCY    => 'BRL',
            SplitPaymentDataRequest::BLOCK_NAME_ID          => __('interest-to-total-%1', $commissionAmount),
            SplitPaymentDataRequest::BLOCK_NAME_DESCRIPTION => __(
                'Interest for %1 installment in %2 total',
                $installment,
                $commissionAmount
            ),
            SplitPaymentDataRequest::BLOCK_NAME_TAX_AMOUNT => ($rule['include_interest']) ? 0 : $this->config->formatPrice($amountInterest),
        ];

        $amountInterestProduct['amount'][$sellerId] = $amountInterest;

        return $amountInterestProduct;
    }
}
