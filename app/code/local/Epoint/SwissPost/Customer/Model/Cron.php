<?php
/**
 * Implement Customer APi crons
 *
 */
class Epoint_SwissPost_Customer_Model_Cron {
	
	/**
	 * Retrieve accounts
	 *
	 */
	function retrieveAccounts(){
		$accounts = Mage::helper('swisspost_api/customer')->searchReadAccount();
		foreach ($accounts as $account){
			try {
  				
			}catch (Exception $e){
				Mage::helper('swisspost_api')->logException($e);		
			}
		}
	}
	/**
	 * Send customers to SwissPost
	 *
	 */
	function sendCustomers(){
		$collection = Mage::getResourceModel('customer/customer_collection');
		// Load all customers without 
		$collection->addAttributeToFilter(
			Epoint_SwissPost_Customer_Model_Resource_Odoo::ATTRIBUTE_CODE_ODOO_ID, 
			array('eq'  => '')
		);
		foreach ($collection as $row){
			try {
  				$customer = Mage::getModel("customer/customer")->load($row->getId());
  				Mage::helper('swisspost_api/customer')->createUpdateAccount($customer);
			}catch (Exception $e){
				Mage::helper('swisspost_api')->logException($e);
			}
  	}
	}
}