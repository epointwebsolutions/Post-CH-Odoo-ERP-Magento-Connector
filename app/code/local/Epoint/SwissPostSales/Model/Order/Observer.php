<?php
 
class Epoint_SwissPostSales_Model_Order_Observer 
{	
	const  ENABLE_SEND_ORDER_CONFIG_PATH = 'swisspost_api/order/enable_realtime';
	/**
	 * Observer on odoo to connect magento account with 
	 * odoo account.
	 *
	 * @param Varien_Event_Observer $observer
	 */
    public function afterOrderSave(Varien_Event_Observer $observer)
    {
    	if(!Mage::getStoreConfig(self::ENABLE_SEND_ORDER_CONFIG_PATH)){
    		return ;
    	}
    	static $processed;
  		$order = $observer->getEvent()->getData('order');
  		if(isset($processed[$order->getId()])){
  			return;
  		}
  		$processed[$order->getId()] = $order->getId();
  		if(Epoint_SwissPostSales_Helper_Data::hasBeenProcessed($order)){
  			return ;
  		}
  		// Send API data.
  		if(!$order->getData(Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID)){
  			$result = Mage::helper('swisspost_api/Order')->createSaleOrder($order);
  		}
    }
    /**
     * Listen: swisspost_api_before_create_order
     *
     * @param Varien_Event_Observer $observer
     */
    function SwissPostApiBeforeCreateOrder(Varien_Event_Observer $observer){
		// Attach products 
  		
  		$order = $observer->getData('order');
  		$sale_order = $observer->getData('sale_order');
  		$sales_order_values = Mage::helper('swisspostsales/Order')->__toSwissPost($order);
  		// Pass values
  	  	foreach ($sales_order_values as $property=>$value){
  		  $sale_order->{$property} = $value;
  		}
  		$orderProducts = $order->getAllVisibleItems();
  		$order_lines = array();
  		foreach ($orderProducts as $item) {
  		    $order_lines[] = Mage::helper('swisspostsales/Product')->__toSwisspostOrderLine($order, $item);
  		}
  		// Attach shipping line
  		$shippingLine = Mage::helper('swisspostsales/Shipping')->__toSwisspostShippingLine($order);
  		if($shippingLine){
  		  $order_lines[] = $shippingLine;
  		}
  		$sale_order->order_lines = $order_lines;
  		$observer->setData('sale_order', $sale_order);
    }
    /**
     * Listen: swisspost_api_after_create_order
     *
     * @param Varien_Event_Observer $observer
     */
    function SwissPostApiAfterCreateOrder(Varien_Event_Observer $observer){
    	$order = $observer->getData('order');
    	$result = $observer->getData('result');
    	// Save timestamp
    	$now = Mage::getModel('core/date')->timestamp();
    	$order->setData(Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_TIMESTAMP, $now)
  				->getResource()->saveAttribute($order, Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_TIMESTAMP);
  		// Save connection.
  		if($result->getResult('odoo_id')){
  			$order->setData(Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID, $result->getResult('odoo_id'))
  				->getResource()->saveAttribute($order, Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID);
  		}
    	$observer->setData('order', $order);
    }
}
