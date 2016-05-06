<?php
/**
 * Shipping Helper
 *
 */
class Epoint_SwissPostSales_Helper_Shipping extends Mage_Core_Helper_Abstract {
  const XML_CONFIG_PATH_SHIPPING_MAPPING = 'swisspost_api/order_shipping_method/mapping2products';
  const XML_CONFIG_PATH_SHIPPING_DEFAULT = 'swisspost_api/order_shipping_method/default_shipping_sku';
  static $_shipping = array();
  /**
   * get shipping method for order, from a product
   *
   * @return object
   */
  public static function getShippingMethodProduct(){
    $configureSku = Mage::getStoreConfig(self::XML_CONFIG_PATH_SHIPPING_DEFAULT);
    if($configureSku){
      return Mage::helper('swisspost_api')->loadProductBySku($configureSku);
    }
    return NULL;
  }
  /**
   * get shipping shipping line
   *
   * @return object
   */
  public static function __toSwisspostShippingLine($order){
    $product = self::getShippingMethodProduct();
    if($product){
      $line = array(
  		    	'product'    => $product->getSku(),
  		    	'name'       => $product->getName(),
  		    	'price_unit' => $order->getShippingAmount(),
  		    	'quantity'   => 1 ,
  		    ); 
      $line = (object)$line;  		    
      // Share action
      Mage::dispatchEvent('swisspost_api_order_prepare_line',
                    		array(
                    			'order'		=> $order,
                    			'product'	=> $product,
                    			'line'		=> $line,
                        	)
                        );  		   
  		return (array)$line;    
    }
  }
  /**
   * get all shipping sku
   * @return array
   */
  public function getShippingSKU(){
    $skus = array();
    $configureSku = Mage::getStoreConfig(self::XML_CONFIG_PATH_SHIPPING_DEFAULT);
    if($configureSku){
      $skus[] = $configureSku;
    }
    // Magento sku|magento shipping method
    $mapping = Mage::helper('swisspost_api')->extractDefaultValues(self::XML_CONFIG_PATH_SHIPPING_MAPPING);
    foreach ($mapping as $sku=>$magento_shipping_method) {
    	if(!in_array($sku, $skus)){
    	  $skus[] = $sku;
    	}
    }
    return $skus;
  }
  /**
   * get all shipping methods
   * @return array
   */
  public static  function getMethods(){
    if(!self::$_shipping){
      $methods = Mage::getSingleton('shipping/config')->getActiveCarriers();
      $shipping = array();
      foreach($methods as $_ccode => $_carrier) {
          if($_methods = $_carrier->getAllowedMethods())  {
              if(!$_title = Mage::getStoreConfig("carriers/$_ccode/title"))
                  $_title = $_ccode;
              foreach($_methods as $_mcode => $_method)   {
                  $_code = $_ccode . '_' . $_mcode;
                  $shipping[$_code]=array('title' => $_method,'carrier' => $_title);
              }
          }
      }
      self::$_shipping = $shipping;
    }
    return self::$_shipping;
  }
  /**
   * get all shipping methods
   * @return array
   */
  public static  function getSKUShippingMethod($sku){
    // Magento sku|magento shipping method
    $mapping = Mage::helper('swisspost_api')->extractDefaultValues(self::XML_CONFIG_PATH_SHIPPING_MAPPING);
    $method = null;
    foreach ($mapping as $conf_sku=>$conf_method){
      if(strtolower(trim($conf_sku)) == strtolower(trim($sku))){
        $method = $conf_method;
        break;
      }
    }
    if($method){
      $methods = self::getMethods();
      return $methods[$method];
    }
  }
  /**
   * convert product to shipping rate
   *
   * @param object $product
   */
  public static function product2Shipping($product){
    // get shipping method
    $shippingMethod = self::getSKUShippingMethod($product->getSku());
    // Share action
    Mage::dispatchEvent('swisspost_api_before_convert_product_to_shipping',
                    		array(
                    			'product'	=> $product,
                        		'shipping_method'	=> $shippingMethod,
                        	)
                        );
    // Change price
    if($shippingMethod && method_exists('setCost')){
      $shippingMethod->setCost($product->getPrice());
    }                    
    Mage::dispatchEvent('swisspost_api_after_convert_product_to_shipping',
                    		array(
                    			'product'	=> $product,
                        		'shipping_method'	=> $shippingMethod,
                        	)
                        );
  }
}