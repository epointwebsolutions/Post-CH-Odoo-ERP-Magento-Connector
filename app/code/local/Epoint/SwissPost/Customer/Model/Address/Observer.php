<?php
 
class Epoint_SwissPost_Customer_Model_Address_Observer 
{	
	//Attach order invoice address
	//address_invoice
	 /**
     * Event invoke: swisspost_api_before_create_order
     *
     */
    function SwissPostApiBeforeCreateOrder(Varien_Event_Observer $observer){
    	$sale_order = $observer->getEvent()->getData('sale_order');
    	$order = $observer->getEvent()->getData('order');
    	$attributeCode = Epoint_SwissPost_Customer_Model_Odoo::ADDRESS_ATTRIBUTE_CODE_ODOO_ID;
    	$billing_address_id = $order->getBillingAddress()->getId();
    	$customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
    	// set billing addres 
    	if($billing_address_id){
    		$address = $order->getBillingAddress();/*
    		$connection = Mage::getModel('swisspost_customer/odoo')->loadByOrder($order);
        	if($connection && !$connection->addressIsConnected($address)){
	    		$result = Mage::helper('swisspost_api/Address')->createUpdateAddress($customer, $address);
        	}*/
    		  // Pass values
          $address_values = Mage::helper('swisspost_customer/address')->__toSwissPost($customer, $address);
    	  $address_values['address_ref'] = $billing_address_id;
      	  $sale_order->address_invoice = $address_values;
    	}
    	// Set invoice address .
    	// If the addresses are same, we will send same billing address
    	if(Mage::helper('swisspost_customer/Address')->areSame($order->getBillingAddress(), $order->getShippingAddress())){
	    	$sale_order->address_shipping = $sale_order->address_invoice;
	    	$sale_order->address_shipping['address_ref'] = $billing_address_id;
    		//unset($sale_order->address_shipping['address_invoice_ref']);
    	}else{
	    	$shipping_address_id = $order->getShippingAddress()->getId();
	    	if($shipping_address_id){
	    		$address = $order->getShippingAddress();
	    		$address_values = Mage::helper('swisspost_customer/address')->__toSwissPost($customer, $address);
	    		$address_values['address_ref'] = $shipping_address_id;
        		$sale_order->address_shipping = $address_values;
	    	}
    	}
    	$observer->setData('sale_order', $sale_order);
    }
    
	/**
	 * Observer on odoo to called before send customer address from magento
	 * odoo 
	 * Event: swisspost_api_after_create_update_address
	 * @param Varien_Event_Observer $observer
	 */
    function SwissPostApiAfterCreateUpdateAddress(Varien_Event_Observer $observer){
    	$customer_address = $observer->getData('customer_address');
    	$attributeCode = Epoint_SwissPost_Customer_Model_Odoo::ADDRESS_ATTRIBUTE_CODE_ODOO_ID;
    	
    	$result = $observer->getData('result');
		// Connect address with
    	if($result->getResult('odoo_id')){
    		$customer_address->setData($attributeCode, $result->getResult('odoo_id'))
    				->getResource()->saveAttribute($customer_address, $attributeCode);
    	}
    	$observer->setData('customer_address', $customer_address);
    }
	/**
	 * Observer on odoo to called before send customer address from magento
	 * odoo 
	 * Event: swisspost_api_before_create_update_address
	 * Attach account ref to it
	 *
	 * @param Varien_Event_Observer $observer
	 */
    function SwissPostApiBeforeCreateUpdateAddress(Varien_Event_Observer $observer){
    	$customer_address  = $observer->getData('customer_address');
    	$customer  = $observer->getData('customer');
    	$address  = $observer->getData('address');
    	if(!$address){
    	  $address_id = $customer->getDefaultBilling();
        if((int)$address_id){
      	 $address = Mage::getModel('customer/address')->load($address_id);
    	  }
    	}
    	// Pass values
      $address_values = Mage::helper('swisspost_customer/address')->__toSwissPost($customer, $customer_address);
      // Pass values
      foreach ($address_values as $property=>$value){
    	  $address->{$property} = $value;
    	}
    	// Attach account ref
    	$connection = Mage::getModel('swisspost_customer/odoo')->loadByCustomer($customer);
    	if(!$connection->__toAccountRef()){
    		$connection = Mage::getModel('swisspost_customer/odoo')->loadByAddress($address);
    	}
    	if($connection){
    		$address->account_ref  = $connection->__toAccountRef();
    	}
    	$observer->setData('address', $address);
    }
}