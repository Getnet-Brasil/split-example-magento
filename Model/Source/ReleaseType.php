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
 * Class ReleaseType - Defines release type.
 */
class ReleaseType implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            'not_applicable' => __('Not Applicable'),
            'cron'           => __('Cron and Manual Release'),
            'manual_release' => __('Manual Release'),
        ];
    }
}
