<?php

/**
 * Product Helper
 *
 */
class Epoint_SwissPostSales_Helper_Product extends Mage_Core_Helper_Abstract
{

    /**
     * Convert a order item into a odoo order create request line
     *
     * @param $order
     * @param $item
     *
     * @return array
     */
    public static function __toSwissPostOrderLine($order, $item)
    {

        $line = new stdClass();
        $line->product = $item->getSku();
        $line->name = $item->getName();
        $line->price_unit = $item->getPrice();
        $line->quantity = $item->getQtyOrdered();
        $line->discount = $item->getDiscountAmount() > 0 && $line->quantity > 0 ? $item->getDiscountAmount() : 0;
        // convert it to percent
        if ($line->discount > 0) {
            $percent = ((($line->discount / $line->quantity) * 100) / $line->price_unit);
            $line->discount = number_format($percent, 2);
        }

        $product = Mage::helper('swisspost_api')->loadProductBySku($item->getSku());

        // Share action
        Mage::dispatchEvent(
            'swisspost_api_order_prepare_line',
            array(
                'order'   => $order,
                'product' => $product,
                'line'    => $line,
            )
        );

        return (array)$line;
    }
}