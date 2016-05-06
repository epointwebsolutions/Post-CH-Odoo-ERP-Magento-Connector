<?php
/**
 * Data Helper
 *
 */
class Epoint_SwissPost_Catalog_Model_Categories extends Mage_Core_Model_Abstract {
  public function import($items = array()){
    // sort asc by odoo_id
    // usort($items, function($a, $b) { return $a['odoo_id'] - $b['odoo_id']; });
    // first import parents
	  Mage::helper('swisspost_catalog/Category')->removeUnused($items);
	
    $imported = array();
    $doImport = true;
    $rounds = 0;
    while ($doImport){
      // import parents first, after that repead until all parents are imports
      foreach ($items as $k=>$item){
        // is a top category or parent has been imports
        if(!$item['parent_id'] || in_array($item['parent_id'], $imported)){
          // was not imported on this executiond
          if(!isset($item['imported'])){
            // import items
            $this->importItem($item);
            $items[$k]['imported'] = 1;
            $imported[] = $item['odoo_id'];
          }
        }
      }
      // max deep is count of items
      if(count($imported) >= count($items)){
        $doImport = false;
      }
      $rounds ++;
      if($doImport && $rounds >= count($items)){
        $doImport = false;
      }
    }
  }
  /**
   * import one item
   */
  public function importItem($_item){
    $logger = Mage::helper('swisspost_api');
    $title = $_item['title'];
    $odoo_id = $_item['odoo_id'];
    $parent_id = $_item['parent_id'];
    $category = Mage::helper('swisspost_catalog/Category')->getByOdooId($odoo_id);
    if($parent_id){
      $parentCategory = Mage::helper('swisspost_catalog/Category')->getByOdooId($parent_id);
    }else{
      $parentCategory = Mage::helper('swisspost_catalog/Category')->getParentByOdooId('');
    }
    $category->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID);
    $category->setName("$title");
    $category->setUrlKey("$title");
    $category->setOdooId($odoo_id);
    $category->setIsActive(1);
    $category->setLevel($parentCategory->getLevel() + 1);
    // do not change the tree
    if($category->getId()){
      $category->setPath($parentCategory->getPath().'/'.$category->getId());
    }else{
      $category->setPath($parentCategory->getPath());
    }
    try{
      $category->save();
      $this->saveStoresAttributes($category, $_item);
      $logger->log($logMsg);
    } catch (Exception $e) {
      $logger->log("ERROR: while processing category with odoo_id=$odoo_id");
      $logger->logException($e);
    }
  }
   /**
   * save attribute value, per website scope
   *
   * @param object $category
   * @param string $item
   */
  public function saveStoresAttributes($category, $item){
  	if(!isset($item[Epoint_SwissPost_Catalog_Model_Products::ODOO_PRODUCT_LANGUAGE_ATTRIBUTE_CODE])){
  		return ;
  	}elseif(!is_array($item[Epoint_SwissPost_Catalog_Model_Products::ODOO_PRODUCT_LANGUAGE_ATTRIBUTE_CODE])){
  		return ;
  	}
    static $mapping;
    if(!isset($mapping)){
      $mapping =  Mage::helper('swisspost_api')->textToArray(Mage::getStoreConfig(Epoint_SwissPost_Catalog_Helper_Data::XML_CONFIG_PATH_STORE_ATTRIBUTE_MAPPING ));
    }
    
    if(!$mapping){
      return ;
    }
    static $stores;
    if(!isset($stores)){
      $stores = Mage::helper('swisspost_api')->getStores();
    }
    foreach ($mapping as $magento_store_code=>$odoo_language_code){
      if(!isset($stores[$magento_store_code])){
        unset($mapping[$magento_store_code]);
      }
    }
    if(!$mapping){
      return ;
    }
    /*    Array
    (
    [title] => Alltagshilfen
    [sequence] => 1
    [languages] => Array
        (
            [fr] => Array
                (
                    [title] => Moyens auxiliaires
                )

            [de] => Array
                (
                    [title] => Alltagshilfen
                )

            [it] => Array
                (
                    [title] => Mezzi ausiliari
                )

        )

    [parent_id] => 
    [odoo_id] => 28
    [path] => All products / Webshop / Alltagshilfen
    */

    foreach ($mapping as $magento_store_code=>$odoo_language_code){
      $values = $item[Epoint_SwissPost_Catalog_Model_Products::ODOO_PRODUCT_LANGUAGE_ATTRIBUTE_CODE];
      if($values && $values[$odoo_language_code]){
        $this->saveStoreAttributes($category, $values[$odoo_language_code], $magento_store_code);
      }
    }
  }
  /**
   * save attribute value, per website scope
   *
   * @param object category
   * @param string $values
   * @param string $store_code
   * @param int $store_code
   */
  public function saveStoreAttributes($category, $values, $store_code){
    static $stores;
    if(!isset($stores)){
      $stores = Mage::helper('swisspost_api')->getStores();
    }
    $store_id = 0;
    if(!isset($stores[$store_code])){
      return ;
    }
    $store_id = $stores[$store_code]->getId();
    // Load mapping
  	$mapping = array('name'=>'title');
  	// Apply mapping, from magento attribute code, 
	  $category_values = Mage::helper('swisspost_api')->__fromSwissPost($values, $mapping, $checkIfSet=true);
  	$updates = array();
  	foreach ($category_values as $attributeCode=>$value){
  		if( $category->getData($attributeCode) != $value){
  	   		$updates[$attributeCode] = $value;
  	  	}
  	}
	 // Exists diffs
    if($updates){
      $resource = Mage::getResourceModel('catalog/category');
      $category->setStoreId($store_id);  
      foreach($updates as $attributeCode=>$value) {
          $category->setData($attributeCode, $value);
          $resource->saveAttribute($category, $attributeCode);
      }
    }
  }
}