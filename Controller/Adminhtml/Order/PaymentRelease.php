<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\SplitExampleMagento\Controller\Adminhtml\Order;

use Getnet\PaymentMagento\Model\Console\Command\Marketplace\PaymentRelease as ModelPaymentRelease;
use Getnet\SplitExampleMagento\Helper\Data;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class Payment Release - Generate Payment Release.
 */
class PaymentRelease extends Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Getnet_SplitExampleMagento::payment_release';

    /**
     * @var ResultFactory
     */
    protected $resultResultFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var ModelPaymentRelease
     */
    protected $modelPaymentRelease;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @param Context                                              $context
     * @param ResultFactory                                        $resultResultFactory
     * @param Json                                                 $json
     * @param Data                                                 $helper
     * @param ModelPaymentRelease                                  $modelPaymentRelease
     * @param ManagerInterface                                     $messageManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime          $date
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        Context $context,
        ResultFactory $resultResultFactory,
        Json $json,
        Data $helper,
        ModelPaymentRelease $modelPaymentRelease,
        ManagerInterface $messageManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->resultResultFactory = $resultResultFactory;
        $this->json = $json;
        $this->helper = $helper;
        $this->modelPaymentRelease = $modelPaymentRelease;
        $this->messageManager = $messageManager;
        $this->date = $date;
        $this->timezone = $timezone;
        parent::__construct($context);
    }

    /**
     * ACL.
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }

    /**
     * Execute.
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $orderId = (int) $this->getRequest()->getParam('order_id');
        $days = $this->helper->getDaysOfRelease();
        $days = $days ? $days : 1;
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $date = new \DateTime();
        $date->add(new \DateInterval('P'.$days.'D'));
        $date = $date->format('Y-m-d\TH:i:s\Z');

        try {
            /** @var $modelPaymentRelease modelPaymentRelease */
            $modelPaymentRelease = $this->modelPaymentRelease->createPaymentRelease($orderId, $date);
            if ($modelPaymentRelease->getSuccess()) {
                $this->messageManager->addSuccess('Payment released');
            }

            if ($modelPaymentRelease->getMessage()) {
                foreach ($modelPaymentRelease->getDetails() as $message) {
                    $messageInfo = __(
                        'Error: %1, description: %2',
                        $message['error_code'],
                        $message['description_detail']
                    );
                    $this->messageManager->addError($messageInfo);
                }
            }
        } catch (\Exception $exc) {
            $this->messageManager->addError($exc->getMessage());
        }

        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}
