<?php
/**
 * Default observer
 *
 */
class Epoint_SwissPost_Catalog_Model_Cron{

    /**
     * observer get products
     *
     * @param Varien_Event_Observer $observer
     */
    public function getProducts() {
      $result = Mage::helper('swisspost_api/Product')->getProducts();
      $products = $result->getValues();
      Mage::getModel('swisspost_catalog/Products')->import($products);
    }
    /**
     * observer get categories
     *
     * @param Varien_Event_Observer $observer
     */
    public function getCategories() {
        $result = Mage::helper('swisspost_api/Product')->getProductCategories();
        $categories = $result->getValues();
        Mage::getModel('swisspost_catalog/Categories')->import($categories);
    }
    /**
     * observer get inventory
     *
     * @param Varien_Event_Observer $observer
     */
    public function getInventory() {
        $products = Mage::helper('swisspost_api/Product')->getProducts();
        $product_ids = array();
        foreach ($products as $product) {
            $product_ids[] = $product['product_code'];
        }
        if(!empty($product_ids)){
            $inventory = Mage::helper('swisspost_api/Product')->getInventory(array('product_codes' => $product_ids))->getValues();
            Mage::getModel('swisspost_catalog/Products')->updateInventory($inventory);
        }
    }



}