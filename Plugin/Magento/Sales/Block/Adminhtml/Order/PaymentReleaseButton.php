<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\SplitExampleMagento\Plugin\Magento\Sales\Block\Adminhtml\Order;

use Getnet\SplitExampleMagento\Helper\Data;
use Magento\Sales\Block\Adminhtml\Order\View;

/**
 * Class Payment Release Button - Add button for payment release.
 */
class PaymentReleaseButton
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Data $config
     */
    public function __construct(
        Data $config
    ) {
        $this->config = $config;
    }

    /**
     * Before Convert.
     *
     * @param View $subject
     */
    public function beforeSetLayout(
        View $subject
    ) {
        $typeRelease = $this->config->getTypeRelease();

        if ($typeRelease !== 'not_applicable') {
            $message = __('Are you sure you want to do this?');
            $url = $subject->getUrl('getnet/order/paymentRelease');

            $subject->addButton(
                'payment_release',
                [
                    'label'   => __('Payment Release'),
                    'onclick' => "confirmSetLocation('{$message}', '{$url}')",
                    'class'   => 'reset',
                ],
                -1
            );
        }
    }
}
