<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\SplitExampleMagento\Cron;

use Getnet\PaymentMagento\Model\Console\Command\Marketplace\PaymentRelease as ModelPaymentRelease;
use Getnet\SplitExampleMagento\Helper\Data;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order\InvoiceRepositoryFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Release By Invoice on Getnet.
 */
class ReleaseByInvoice
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var InvoiceRepositoryFactory
     */
    protected $invoiceRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ModelPaymentRelease
     */
    protected $modelPaymentRelease;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * Constructor.
     *
     * @param LoggerInterface                                      $logger
     * @param Data                                                 $helper
     * @param ModelPaymentRelease                                  $modelPaymentRelease
     * @param InvoiceRepositoryFactory                             $invoiceRepository
     * @param SearchCriteriaBuilder                                $searchCriteriaBuilder
     * @param \Magento\Framework\Stdlib\DateTime\DateTime          $date
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        LoggerInterface $logger,
        Data $helper,
        ModelPaymentRelease $modelPaymentRelease,
        InvoiceRepositoryFactory $invoiceRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->modelPaymentRelease = $modelPaymentRelease;
        $this->invoiceRepository = $invoiceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->date = $date;
        $this->timezone = $timezone;
    }

    /**
     * Execute the cron.
     *
     * @return void
     */
    public function execute()
    {
        $this->logger->debug('Cronjob Release By Invoice');

        $dateNow = new \DateTime();
        $dateTo = $dateNow->format('Y-m-d 23:59:59');
        $dateFrom = $dateNow->format('Y-m-d 00:00:00');

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('created_at', $dateFrom, 'gteq')
            ->addFilter('created_at', $dateTo, 'lteq')
            ->create();

        $invoices = $this->invoiceRepository->create()->getList($searchCriteria);

        $days = $this->helper->getDaysOfRelease();
        $days = $days ? $days : 1;

        $dateNow->add(new \DateInterval('P'.$days.'D'));
        $dateToRelease = $dateNow->format('Y-m-d\TH:i:s\Z');

        foreach ($invoices->getItems() as $invoice) {
            $order = $invoice->getOrder();
            $payment = $order->getPayment()->getMethodInstance();
            if ($payment->getCode() === 'getnet_paymentmagento_boleto'
                || $payment->getCode() === 'getnet_paymentmagento_cc'
            ) {
                $orderId = $order->getId();
                $this->logger->debug(sprintf('Invoice id %s, order id %s', $invoice->getId(), $orderId));
                $this->modelPaymentRelease->createPaymentRelease($orderId, $dateToRelease);
            }
        }

        $this->logger->debug('Cronjob Release By Invoice is done.');
    }
}
