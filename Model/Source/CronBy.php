<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Getnet\SplitExampleMagento\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class CronBy - Defines type cron.
 */
class CronBy implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            'shipping' => __('Shipping'),
            'invoice'  => __('Invoice'),
        ];
    }
}
