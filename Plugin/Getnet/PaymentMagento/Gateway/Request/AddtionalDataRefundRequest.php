<?php
/**
 * Copyright © O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Getnet\SplitExampleMagento\Plugin\Getnet\PaymentMagento\Gateway\Request;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use Getnet\PaymentMagento\Gateway\Request\RefundRequest;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use Getnet\SplitExampleMagento\Helper\Data as SplitHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory as TransactionSearch;

/**
 * Class Addtional Data for Refund - add marketplace data in Transaction.
 */
class AddtionalDataRefundRequest
{
    public const MARKETPLACE_SUBSELLER_PAYMENTS = 'marketplace_subseller_payments';

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
     * @var TransactionSearch
     */
    protected $transactionSearch;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param SubjectReader        $subjectReader
     * @param OrderAdapterFactory  $orderAdapterFactory
     * @param Config               $config
     * @param SplitHelper          $splitHelper
     * @param TransactionSearch    $transactionSearch
     * @param Json                 $json
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        Config $config,
        SplitHelper $splitHelper,
        TransactionSearch $transactionSearch,
        Json $json,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->config = $config;
        $this->splitHelper = $splitHelper;
        $this->transactionSearch = $transactionSearch;
        $this->json = $json;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Around method Build.
     *
     * @param RefundRequest $subject
     * @param \Closure      $proceed
     * @param array         $buildSubject
     *
     * @return mixin
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundBuild(
        RefundRequest $subject,
        \Closure $proceed,
        $buildSubject
    ) {
        $result = $proceed($buildSubject);

        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDO->getOrder();

        $orderId = $order->getId();

        $transaction = $this->transactionSearch->create()->addOrderIdFilter($orderId)->getFirstItem();

        $sellersItems = $transaction->getOrder()->getPayment()->getAdditionalInformation('marketplace');

        $sellersItems = $this->json->unserialize($sellersItems);

        if (is_array($sellersItems)) {
            foreach ($sellersItems as $sellerId => $items) {
                $sellers = ['subseller_id' => $sellerId];

                $sellerItems = ['order_items' => $items];

                $amountSub = array_sum(array_column($sellersItems[$sellerId], 'amount'));

                $subAmount = ['subseller_sales_amount' => $amountSub];
            }
            $formart = array_merge_recursive($sellers, $subAmount, $sellerItems);
            $result = array_merge($result, [self::MARKETPLACE_SUBSELLER_PAYMENTS => $formart]);
        }

        return $result;
    }
}
