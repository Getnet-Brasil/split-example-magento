<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\SplitExampleMagento\Gateway\Request\Boleto;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use Getnet\PaymentMagento\Gateway\Request\Boleto\BoletoInitSchemaDataRequest;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use Getnet\SplitExampleMagento\Helper\Data as SplitHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class Split Payment Data Request - Payment amount structure.
 */
class SplitPaymentDataRequest implements BuilderInterface
{
    public const BLOCK_NAME_ADDITIONAL_DATA = 'additional_data';
    public const BLOCK_NAME_SPLIT = 'split';
    public const BLOCK_NAME_SUBSELLER_LIST_PAYMENT = 'subseller_list_payment';
    public const BLOCK_NAME_SUB_SELLER_ID = 'subseller_id';
    public const BLOCK_NAME_SUBSELLER_SALES_AMOUNT = 'subseller_sales_amount';
    public const BLOCK_NAME_ORDER_ITEMS = 'order_items';
    public const BLOCK_NAME_AMOUNT = 'amount';
    public const BLOCK_NAME_CURRENCY = 'currency';
    public const BLOCK_NAME_ID = 'id';
    public const BLOCK_NAME_DESCRIPTION = 'description';
    public const GUARANTOR_DOCUMENT_TYPE = 'document_type';
    public const GUARANTOR_DOCUMENT_NUMBER = 'document_number';

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
     * @param SplitHelper          $splitHelper
     * @param Json                 $json
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        Config $config,
        SplitHelper $splitHelper,
        Json $json,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->config = $config;
        $this->splitHelper = $splitHelper;
        $this->json = $json;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
        || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();

        $result = [];

        $marketplace = [];

        /** @var OrderAdapterFactory $orderAdapter * */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $order = $paymentDO->getOrder();

        $dataSellers = $this->getDataForSplit($order);

        $shippingAmount = $orderAdapter->getShippingAmount();

        $payment = $paymentDO->getPayment();

        $storeId = $order->getStoreId();

        if (!isset($dataSellers['productBySeller'])) {
            return $result;
        }

        $typeDocument = 'CPF';

        $document = $this->splitHelper->getAdditionalGuarantorNumber($storeId);

        $document = preg_replace('/[^0-9]/', '', $document);

        if (strlen($document) === 14) {
            $typeDocument = 'CNPJ';
        }

        foreach ($dataSellers['productBySeller'] as $sellerId => $products) {
            $priceShippingBySeller = 0;
            $productAmount = array_sum(array_column($dataSellers['pricesBySeller'][$sellerId], 'totalAmount'));

            $shippingProduct = $this->addSplitShippingInSellerData($order, $shippingAmount, $sellerId, $dataSellers);

            $products['product'][] = $shippingProduct['products'][$sellerId];

            $priceShippingBySeller = $shippingProduct['amount'][$sellerId];

            $commissionAmount = $productAmount + $priceShippingBySeller;

            $result[BoletoInitSchemaDataRequest::DATA]
                [self::BLOCK_NAME_ADDITIONAL_DATA]
                [self::BLOCK_NAME_SPLIT][self::BLOCK_NAME_SUBSELLER_LIST_PAYMENT][] = [
                    self::BLOCK_NAME_SUB_SELLER_ID          => $sellerId,
                    self::GUARANTOR_DOCUMENT_TYPE           => $typeDocument,
                    self::GUARANTOR_DOCUMENT_NUMBER         => $document,
                    self::BLOCK_NAME_SUBSELLER_SALES_AMOUNT => $this->config->formatPrice($commissionAmount),
                    self::BLOCK_NAME_ORDER_ITEMS            => $products['product'],
                ];
            }

        foreach ($result[BoletoInitSchemaDataRequest::DATA][self::BLOCK_NAME_ADDITIONAL_DATA][self::BLOCK_NAME_SPLIT][self::BLOCK_NAME_SUBSELLER_LIST_PAYMENT] as $sellers)
        {
            $seller = $sellers[self::BLOCK_NAME_SUB_SELLER_ID];
            $marketplace[$seller] = $sellers[self::BLOCK_NAME_ORDER_ITEMS];
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

            $data['productBySeller'][$sellerId]['product'][] = [
                self::BLOCK_NAME_AMOUNT      => $this->config->formatPrice($price),
                self::BLOCK_NAME_CURRENCY    => $order->getCurrencyCode(),
                self::BLOCK_NAME_ID          => $item->getSku(),
                self::BLOCK_NAME_DESCRIPTION => __(
                    'Product Name: %1 | Qty: %2',
                    $item->getName(),
                    $item->getQtyOrdered()
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

        $shippingProduct['products'][$sellerId] = [
            self::BLOCK_NAME_AMOUNT      => $this->config->formatPrice($priceShippingBySeller),
            self::BLOCK_NAME_CURRENCY    => $order->getCurrencyCode(),
            self::BLOCK_NAME_ID          => __('shipping-order-%1', $order->getOrderIncrementId()),
            self::BLOCK_NAME_DESCRIPTION => __('Shipping for %1 products', $qtyOrderedBySeller),
        ];

        $shippingProduct['amount'][$sellerId] = $priceShippingBySeller;

        return $shippingProduct;
    }
}
