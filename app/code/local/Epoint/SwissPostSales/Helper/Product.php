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
    if($line->price_unit  > 0 && $line->quantity > 0){
      $attribute = Mage::getModel('eav/entity_attribute')
        ->loadByCode('catalog_product', self::PRODUCT_ORIGINAL_PRICE_ATTRIBUTE_CODE);

      if($attribute){
        $product = Mage::getModel('catalog/product')->load($item->getProductId());
        if($product){
          $line->price_unit = number_format($product->getData(self::PRODUCT_ORIGINAL_PRICE_ATTRIBUTE_CODE), 4);
          $line->discount = $product->getData(self::PRODUCT_ORIGINAL_PRICE_ATTRIBUTE_CODE)
            - $product->getPrice();
          if($line->discount > 0){
            $percent = ($line->discount * 100) / $product->getData(self::PRODUCT_ORIGINAL_PRICE_ATTRIBUTE_CODE);
            $line->discount = number_format($percent, 4);
          }
        }
      }else{
        $percent = ((($item->getDiscountAmount() / $line->quantity) * 100) / $line->price_unit);
        $line->discount = number_format($percent, 4);
      }
    }
    // fix
    if ($line->discount < 0) {
      $line->discount = 0;
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

  /**
   * Convert the order discount into odoo order line.
   *
   * @param $order
   *
   * @return array
   */
  public static function __toSwissPostDiscountOrderLine($order)
  {
    if (Mage::getStoreConfig(Epoint_SwissPostSales_Helper_Order::ENABLE_ORDER_DISCOUNT_PRODUCT_LINE)) {
      if($order->getDiscountAmount() <> 0){
        $discount_amount = abs($order->getDiscountAmount());
        $config  = Epoint_SwissPost_Api_Helper_Data::extractDefaultValues(
          Epoint_SwissPostSales_Helper_Order::ORDER_DISCOUNT_PRODUCT_LINE
        );
        $line = new stdClass();
        foreach ($config as $property=>$value){
          $line->{$property} = $value;
        }
        if(isset($line->tax_id) && !is_array($line->tax_id)){
          $line->tax_id = array_map('trim', explode(',', $line->tax_id));
        }

        if(isset($line->price_unit)){
          $line->price_unit = number_format((float)$line->price_unit - $discount_amount, 2);
        };
        // Share action
        Mage::dispatchEvent(
          'swisspost_api_order_prepare_discount_line',
          array(
            'order'   => $order,
            'line'    => $line,
          )
        );
        return (array)$line;
      }
    }
    return null;
  }
}