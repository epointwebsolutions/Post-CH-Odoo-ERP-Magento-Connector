<?php
 
class Epoint_SwissPost_Catalog_Model_Order_Observer 
{	
	
	/**
	 * Observer on event swisspost_api_order_prepare_line
	 * 
	 *
	 * @param Varien_Event_Observer $observer, add tax item
	 */
    public function prepareLine(Varien_Event_Observer $observer)
    {
      // Load line.
      $line = $observer->getLine();
      
      $product = $observer->getProduct();
      // Check connection
      if($line){
      	$odoo_tax_class_id =  Mage::helper('swisspost_catalog')->__toSiwssPostTaxClassId($product);
    		if($odoo_tax_class_id){
    			$line->tax_id = array($odoo_tax_class_id);
    		}
  		  $observer->setData('line', $line);
      }
    }
}
