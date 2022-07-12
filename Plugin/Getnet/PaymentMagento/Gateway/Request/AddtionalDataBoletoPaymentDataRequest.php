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
use Getnet\PaymentMagento\Gateway\Request\BoletoPaymentDataRequest;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use Getnet\SplitExampleMagento\Helper\Data as SplitHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Addtional Data Boleto Payment Data Request - add marketplace data in Transaction.
 */
class AddtionalDataBoletoPaymentDataRequest
{
    public const GUARANTOR_DOCUMENT_TYPE = 'guarantor_document_type';
    public const GUARANTOR_DOCUMENT_NUMBER = 'guarantor_document_number';
    public const GUARANTOR_NAME = 'guarantor_name';

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
     * @param SubjectReader        $subjectReader
     * @param OrderAdapterFactory  $orderAdapterFactory
     * @param Config               $config
     * @param SplitHelper          $splitHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        Config $config,
        SplitHelper $splitHelper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->config = $config;
        $this->splitHelper = $splitHelper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Around method Build.
     *
     * @param BoletoPaymentDataRequest $subject
     * @param \Closure                 $proceed
     * @param array                    $buildSubject
     *
     * @return mixin
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundBuild(
        BoletoPaymentDataRequest $subject,
        \Closure $proceed,
        $buildSubject
    ) {
        $result = $proceed($buildSubject);

        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDO->getOrder();

        $storeId = $order->getStoreId();

        $items = $order->getItems();

        $availlable = false;

        foreach ($items as $item) {
            if ($item->getProduct()->getGetnetSubSellerId()) {
                $availlable = true;
            }
        }

        if (!$availlable) {
            return $result;
        }

        $typeDocument = 'CPF';

        $name = $this->splitHelper->getAdditionalGuarantorName($storeId);

        $document = $this->splitHelper->getAdditionalGuarantorNumber($storeId);

        $document = preg_replace('/[^0-9]/', '', $document);

        if (strlen($document) === 14) {
            $typeDocument = 'CNPJ';
        }

        $addtionalData = [
            self::GUARANTOR_DOCUMENT_TYPE   => $typeDocument,
            self::GUARANTOR_DOCUMENT_NUMBER => $document,
            self::GUARANTOR_NAME            => $name,
        ];

        $result[BoletoPaymentDataRequest::METHOD] = array_merge(
            $result[BoletoPaymentDataRequest::METHOD],
            $addtionalData
        );

        return $result;
    }
}
