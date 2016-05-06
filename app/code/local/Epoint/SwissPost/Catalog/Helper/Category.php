<?php
/**
 * Data Helper
 *
 */
class Epoint_SwissPost_Catalog_Helper_Category extends Mage_Core_Helper_Abstract {
  const CATEGORY_ATTIBUTE_CODE = 'odoo_id';
  /**
   * load categories by atrribute
   *
   * @param unknown_type $odoo_id
   * @return unknown
   */
  public static function getByOdooId($odoo_id){
    $category = Mage::getModel('catalog/category')->loadByAttribute(self::CATEGORY_ATTIBUTE_CODE, $odoo_id);
    if($category && $category->getId()){
      return $category;
    }else{
      return Mage::getModel('catalog/category');
    }
  } 
  /**
   * load categories by atrribute
   *
   * @param unknown_type $odoo_id
   * @return unknown
   */
  public static function getFromStaticByOdooId($odoo_id){
    static $loaded;
    if(isset($loaded[$odoo_id])){
      return $loaded[$odoo_id];
    }
    $loaded[$odoo_id] = Mage::getModel('catalog/category')->loadByAttribute(self::CATEGORY_ATTIBUTE_CODE, $odoo_id);
    return $loaded[$odoo_id];
  }
  /**
   * load categories by atrribute
   *
   * @param unknown_type $odoo_id
   * @return unknown
   */
  public static function getParentByOdooId($odoo_id){
    if($odoo_id){
      $category = self::getByOdooId($odoo_id);
    }
    if(!$odoo_id || !$$category || !$category->getId()){
      $store = Mage::getModel('core/store')->load(Mage_Core_Model_App::DISTRO_STORE_ID);
      $rootId = $store->getRootCategoryId();
      $parentCat = Mage::getModel('catalog/category')->load($rootId); 
    }else{
      $parentCat = $category->getParentCategory();
    }
    if(!$parentCat->getId()){
      $store = Mage::getModel('core/store')->load(Mage_Core_Model_App::DISTRO_STORE_ID);
      $rootId = $store->getRootCategoryId();
      $parentCat = Mage::getModel('catalog/category')->load($rootId); 
    }
    return $parentCat;
  }
  /**
   * load categories by atrribute
   *
   * @param unknown_type $odoo_id
   * @return unknown
   */
  public static function getCategoriesId4Product($category){
   $ids = array($category->getId());
   $path = explode('/', $category->getPath());
   // remove default categories
   unset($path[0]);
   unset($path[1]);
   foreach ($path as $id){
     if(!in_array($id, $ids)){
       $ids[] = $id;
     }
   }
   return $ids;
  }
  /**
   * Remove items based on config
   */
  public static function removeUnused(&$items){
  	$categories = explode(",", trim(Mage::getStoreConfig(Epoint_SwissPost_Catalog_Helper_Data::XML_CONFIG_IMPORT_CATEGORY_FROM)));
  	if(!$categories){
  		return ;
  	}
  	$removedItems = $categories;
  	$continue = $removedItems ? TRUE : FALSE;
  	$added = 0;
  	// Remove items and children
  	while ($continue){
  		$count = count($removedItems);
  		$added = 0;
	  	foreach ($items as $k=>$item){
	  		if(in_array($item['odoo_id'], $removedItems)){
	  			unset($items[$k]);
	  			$added++;
	  			if(!in_array($item['odoo_id'], $removedItems)){
  					$removedItems[] = $item['odoo_id'];
	  			}
	  			if(!in_array($item['parent_id'], $removedItems) && !in_array($item['parent_id'], $categories)){
	  				$removedItems[] = $item['parent_id'];
	  			}
	  		}
	  	}
	  	if($added == 0){
	  		$continue = false;
	  	}
  	}
  	// remove orphans, we are doing three remove
  	$odoo_ids  = array();
  	foreach ($items as $k=>$item){
		$odoo_ids[$item['odoo_id']] = $item['odoo_id'];
  	}
  	$continue = true;
  	while ($continue){
  		$added = 0;
	  	foreach ($items as $k=>$item){
	  		if(!in_array($item['parent_id'], $odoo_ids) && !in_array($item['parent_id'], $categories)){
	  			unset($items[$k]);
	  			unset($odoo_ids[$k]);
	  			$added++;
	  			$removedItems[] = $item['odoo_id'];
	  		}
	  	}
	  	if($added == 0){
	  		$continue = false;
	  	}
  	}
  	// fix parents to remains items
  	foreach ($items as $k=>$item){
  		if(in_array($item['parent_id'], $categories)){
  			$items[$k]['parent_id'] = null;
  		}
  	}
  }
}