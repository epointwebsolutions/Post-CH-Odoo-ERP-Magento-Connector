<?php

/**
 * Default observer
 *
 */
class Epoint_SwissPost_Catalog_Model_Cron
{

    /**
     * Cron get products
     */
    public function getProducts()
    {
        try{
          $options = array();
          if(Mage::getStoreConfig(Epoint_SwissPost_Catalog_Helper_Data::XML_CONFIG_IMPORT_LIMIT) > 0){
            $options['limit'] = (int)Mage::getStoreConfig(Epoint_SwissPost_Catalog_Helper_Data::XML_CONFIG_IMPORT_LIMIT);
          }
          $last_import_timestamp = strtotime(Mage::getStoreConfig(Epoint_SwissPost_Catalog_Helper_Data::XML_CONFIG_IMPORT_LAST_DATE));
          
          if(!$last_import){
            $last_import_date = date('Y-m-d', strtotime('3 days'));
          }else{
            $last_import_date = date('Y-m-d', $last_import_timestamp);
          }
          if(Mage::getStoreConfig(Epoint_SwissPost_Catalog_Helper_Data::XML_CONFIG_ENABLE_IMPORT_FILTER_CHANGED)){
            $options['filters'] =  array('write_date >= '.$last_import_date);
          }
          // add custom filter
          if(Mage::getStoreConfig(Epoint_SwissPost_Catalog_Helper_Data::XML_CONFIG_IMPORT_CUSTOM_ORDER)){
            $options['order'] = Mage::getStoreConfig(Epoint_SwissPost_Catalog_Helper_Data::XML_CONFIG_IMPORT_CUSTOM_ORDER);
          }
          $result = Mage::helper('swisspost_api/Product')->getProducts($options);
          $products = $result->getValues();
          Mage::getModel('swisspost_catalog/Products')->import($products);
          // save last date
          Mage::getModel('core/config')->saveConfig(Epoint_SwissPost_Catalog_Helper_Data::XML_CONFIG_IMPORT_LAST_DATE, $last_import_date);
        }catch (Exception $e){
          Epoint_SwissPost_Api_Helper_Data::LogException($e);
        }
    }

    /**
     * Cron get categories
     */
    public function getCategories()
    {
        try {
          $result = Mage::helper('swisspost_api/Product')->getProductCategories();
          $categories = $result->getValues();
          Mage::getModel('swisspost_catalog/Categories')->import($categories);
        }catch (Exception $e){
          Epoint_SwissPost_Api_Helper_Data::LogException($e);
        }
    }

    /**
     * Cron get inventory
     */
    public function getInventory()
    {
        try{
          $products = Mage::helper('swisspost_api/Product')->getProducts(array(
              'fields'=>array('product_code'),
            )
          );
          $product_ids = array();
          foreach ($products as $product) {
              $product_ids[] = $product['product_code'];
          }
          
          if (!empty($product_ids)) {
              $inventory = Mage::helper('swisspost_api/Product')->getInventory(array('product_codes' => $product_ids))
                  ->getValues();
              Mage::getModel('swisspost_catalog/Products')->updateInventory($inventory);
          }
        }catch (Exception $e){
          Epoint_SwissPost_Api_Helper_Data::LogException($e);
        }
    }
}