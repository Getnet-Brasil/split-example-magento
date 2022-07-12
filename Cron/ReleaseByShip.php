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
use Magento\Sales\Model\Order\ShipmentRepositoryFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Release By Ship on Getnet.
 */
class ReleaseByShip
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
     * @var ShipmentRepositoryFactory
     */
    protected $shipRepository;

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
     * @param ShipmentRepositoryFactory                            $shipRepository
     * @param SearchCriteriaBuilder                                $searchCriteriaBuilder
     * @param \Magento\Framework\Stdlib\DateTime\DateTime          $date
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        LoggerInterface $logger,
        Data $helper,
        ModelPaymentRelease $modelPaymentRelease,
        ShipmentRepositoryFactory $shipRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->modelPaymentRelease = $modelPaymentRelease;
        $this->shipRepository = $shipRepository;
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
        $this->logger->debug('Cronjob Release By Ship');

        $dateNow = new \DateTime();
        $dateTo = $dateNow->format('Y-m-d 23:59:59');
        $dateFrom = $dateNow->format('Y-m-d 00:00:00');

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('created_at', $dateFrom, 'gteq')
            ->addFilter('created_at', $dateTo, 'lteq')
            ->create();

        $ships = $this->shipRepository->create()->getList($searchCriteria);

        $days = $this->helper->getDaysOfRelease();
        $days = $days ? $days : 1;

        $dateNow->add(new \DateInterval('P'.$days.'D'));
        $dateToRelease = $dateNow->format('Y-m-d\TH:i:s\Z');

        foreach ($ships->getItems() as $ship) {
            $order = $ship->getOrder();
            $payment = $order->getPayment()->getMethodInstance();
            if ($payment->getCode() === 'getnet_paymentmagento_boleto'
                || $payment->getCode() === 'getnet_paymentmagento_cc'
            ) {
                $orderId = $order->getId();
                $this->logger->debug(sprintf('ship id %s, order id %s', $ship->getId(), $orderId));
                $this->modelPaymentRelease->createPaymentRelease($orderId, $dateToRelease);
            }
        }

        $this->logger->debug('Cronjob Release By ship is done.');
    }
}
