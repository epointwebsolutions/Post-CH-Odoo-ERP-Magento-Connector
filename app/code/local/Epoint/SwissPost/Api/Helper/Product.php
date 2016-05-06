<?php
/**
 * Data Helper
 *
 */
class Epoint_SwissPost_Api_Helper_Product extends Mage_Core_Helper_Abstract {
  /**
   * Return information about all products. The search can be filtered by search criteria and the returned information can be limited to a selection of fields.
   * POST /get_products
   * $otions['filters'], required  Search filters. See Search filters chapter. Example: ['product_code = ABC']
   * $otions['fields'], required,  If provided, the response returns only the asked fields. Example: ['product_code', 'title', 'description_short']. Otherwise, if this is an empty list, it will return all fields detailed below
   * $otions['offset'], not required  Set an offset for reading rows (default: 0, meaning no offset)
   * @see http://post-ecommerce-xml.braintec-group.com/doc/Swisspost-WS-v2-doc/get_products.html
   * @param array of options $options
   * @return result object of products
   */
  public function getProducts($options = array()){
    // Required fields.
    // Search filters. See Search filters chapter. Example: ['product_code = ABC']
    if(!isset($options['filters'])){
      $options['filters'] = array();
    }
    // If provided, the response returns only the asked fields. Example: ['product_code', 'title', 'description_short']. Otherwise, if this is an empty list, it will return all fields detailed below
    if(!isset($options['fields'])){
      $options['fields'] = array();
    }
    $helper = Mage::helper('swisspost_api/Api');
    return $helper->call('get_products', $options);
  }
  /**
   * Implement methods for getting product images
   * @param product ref string
   * @return result object
   *
   */
  public function getImages($product_ref = string){
    // Required field
    if(!$product_ref){
      return array();
    }
    $options['product_ref'] = $product_ref;
    $helper = Mage::helper('swisspost_api/Api');
    return $helper->call('get_images', $options);
  }
  /**
   * Return information about all product categories. The search can be filtered by search criteria and the returned information can be limited to a selection of fields.
   * POST /get_product_categories
   * $otions['filters'], required  Search filters. See Search filters chapter. Example: ['product_code = ABC']
   * $otions['fields'], required,  If provided, the response returns only the asked fields. Example: ['product_code', 'title', 'description_short']. Otherwise, if this is an empty list, it will return all fields detailed below
   * $otions['offset'], not required  Set an offset for reading rows (default: 0, meaning no offset)
   * @see http://post-ecommerce-xml.braintec-group.com/doc/Swisspost-WS-v2-doc/get_product_categories.html
   * @param product ref string
   * @return result object
   *
   */
  public function getProductCategories($options = array()){
    $helper = Mage::helper('swisspost_api/Api');
    // Required fields.
    // Search filters. See Search filters chapter. Example: ['product_code = ABC']
    if(!isset($options['filters'])){
      $options['filters'] = array();
    }
    //If provided, the response returns only the asked fields. Example: ['product_code', 'title', 'description_short']. Otherwise, if this is an empty list, it will return all fields detailed below
    if(!isset($options['fields'])){
      $options['fields'] = array();
    }
    return $helper->call('get_product_categories', $options);
  }
  /**
   * Retrieve the quantity on sale for a list of product codes.
   * POST /get_inventory
   * $otions['product_codes'], required  Search filters. See Search filters chapter. Example: ['product_code = ABC']
   * @see http://post-ecommerce-xml.braintec-group.com/doc/Swisspost-WS-v2-doc/get_product_categories.html
   * @param product ref string
   * @return result object
   *
   */
  public function getInventory($options = array()){
    // Required fields.
    // Search filters. See Search filters chapter. Example: ['product_code = ABC']
    if(!isset($options['product_codes'])){
      $options['product_codes'] = array();
    }
    $helper = Mage::helper('swisspost_api/Api');
    return $helper->call('get_inventory', $options);
  }
}