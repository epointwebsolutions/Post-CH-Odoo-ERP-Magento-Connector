<?php
 
class Epoint_SwissPostSales_Model_Shipping_Cron
{	
	/**
	 * Receive shipping products and inform system about them
	 *
	 */
    public function update(){
      //
      $skus = Mage::helper('swisspostsales/Shipping')->getShippingSKU();
     
      // Sync shipping products 
      $products = array();
      foreach ($skus as $sku){
        $options['filters'] = array('product_code = '.$sku);
        $result = Mage::helper('swisspost_api/Product')->getProducts($options);
        $items = $result->getValues();
        foreach ($items as $item){
          $products[] = $item;
        }
      }
      // 
      if($products){
        Mage::getModel('swisspost_catalog/Products')->import($products);
        foreach ($skus as $sku){
          $product = Mage::helper('swisspost_api')->loadProductBySku($sku);
          Mage::helper('swisspostsales/Shipping')->product2Shipping($product);
        }
      }
    }
}
