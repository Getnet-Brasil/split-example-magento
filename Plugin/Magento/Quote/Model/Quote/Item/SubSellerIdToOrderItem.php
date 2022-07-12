<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\SplitExampleMagento\Plugin\Magento\Quote\Model\Quote\Item;

use Magento\Quote\Model\Quote\Address\Item as AddressItem;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\ToOrderItem;

/**
 * Class Sub Seller Id To Order Item - Add Sub Seller Id in Item.
 */
class SubSellerIdToOrderItem
{
    /**
     * Around Convert.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param ToOrderItem      $subject
     * @param \Closure         $proceed
     * @param Item|AddressItem $quoteItem
     * @param array            $data
     */
    public function aroundConvert(
        ToOrderItem $subject,
        \Closure $proceed,
        $quoteItem,
        array $data
    ) {
        $orderItem = $proceed($quoteItem, $data);

        $subSellerId = $quoteItem->getGetnetSubSellerId();

        $orderItem->setGetnetSubSellerId($subSellerId);

        return $orderItem;
    }
}
