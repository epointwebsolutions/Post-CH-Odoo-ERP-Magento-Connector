<?php

/**
 * Product Helper
 */
class Epoint_SwissPostSales_Helper_Product extends Mage_Core_Helper_Abstract
{
  /**
   * Original product price
   *
   */
  CONST PRODUCT_ORIGINAL_PRICE_ATTRIBUTE_CODE = 'originalpreis';
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
    $line->discount = 0;
    $line->quantity = 5;
    if($line->price_unit  > 0 && $line->quantity > 0){
      $attribute = Mage::getModel('eav/entity_attribute')
        ->loadByCode('catalog_product', self::PRODUCT_ORIGINAL_PRICE_ATTRIBUTE_CODE);

      if($attribute){
        $product = Mage::getModel('catalog/product')->load($item->getProductId());
        if($product){
          print $product->getData(self::PRODUCT_ORIGINAL_PRICE_ATTRIBUTE_CODE);
          $line->discount =  ($product->getData(self::PRODUCT_ORIGINAL_PRICE_ATTRIBUTE_CODE)
              - $product->getPrice()) * $line->quantity;
        }
      }else{
        $this->discount = $item->getDiscountAmount();
      }
    }
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