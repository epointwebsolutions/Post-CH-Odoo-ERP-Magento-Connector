<?php

class Epoint_SwissPostSales_Model_Order_Observer
{
    const  ENABLE_SEND_ORDER_CONFIG_PATH = 'swisspost_api/order/enable_realtime';

    /**
     * Observer convert invoice to order
     *
     * @param Varien_Event_Observer $observer
     */
    public function afterOrderSave(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getData('order');
        if(Mage::helper('swisspostsales/Order')->canInvoice($order)){
          Mage::helper('swisspostsales/Order')->__toInvoice($order);
        }
    }
    /**
     * Listen: swisspost_api_before_create_order
     *
     * @param Varien_Event_Observer $observer
     */
    public function SwissPostApiBeforeCreateOrder(Varien_Event_Observer $observer)
    {
        // Attach products
        $order = $observer->getData('order');
        $sale_order = $observer->getData('sale_order');
        $sales_order_values = Mage::helper('swisspostsales/Order')->__toSwissPost($order);
        // Pass values
        foreach ($sales_order_values as $property => $value) {
            $sale_order->{$property} = $value;
        }
        $orderProducts = $order->getAllVisibleItems();
        $order_lines = array();
        foreach ($orderProducts as $item) {
            $order_lines[] = Mage::helper('swisspostsales/Product')->__toSwisspostOrderLine($order, $item);
        }
        // Attach shipping line
        $shippingLine = Mage::helper('swisspostsales/Shipping')->__toSwisspostShippingLine($order);
        if ($shippingLine) {
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
    public function SwissPostApiAfterCreateOrder(Varien_Event_Observer $observer)
    {
        $order = $observer->getData('order');
        $result = $observer->getData('result');
        // Save timestamp
        $now = Mage::getModel('core/date')->timestamp();
        // Set timestamp for API call
        $order->setData(Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_TIMESTAMP, $now)
	        ->getResource()->saveAttribute(
	            $order, Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_TIMESTAMP
	        );
        // Save connection.
        if ($result->getResult('odoo_id')) {
        	// set oddoo id
            $order->setData(
                Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID, $result->getResult('odoo_id')
            );
            // Set state processing.
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, TRUE);
            $order->save();
            
            // Add comment    
            Mage::helper('swisspostsales/Order')->addComment(
                $order,
                Mage::helper('core')->__('Send order return OK from Odoo with #ID: %s', $result->getResult('odoo_id'))
            );
            // Notify ok
            Mage::helper('swisspostsales/Order')->Notify(
                Mage::helper('core')->__(
                    'Send order #%s return OK: %s', $order->getIncrementId(), $result->getResult('odoo_id')
                ),
                Mage::helper('core')->__(
                    'Odoo id: %s
                DEBUG: %s',
                    is_array($result->getResult('odoo_id')) ? implode("\n", $result->getResult('odoo_id'))
                        : $result->getResult('odoo_id'),
                    implode("\n", $result->getDebug())
                )
                , $error = false
            );
        } else {
        	// result is an API error
        	if($result->isValidAPIError()){
        		// remain on pending, 
        		if($order->getState() != Mage_Sales_Model_Order::STATE_PENDING_PAYMENT){
        			$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, TRUE);
        			$order->setStatus('processing');
        			$order->save();
        		}
        	}else{
        		// Set it on hold
        		if($order->getState() != Mage_Sales_Model_Order::STATE_HOLDED){
	            	$order->setState(Mage_Sales_Model_Order::STATE_HOLDED);
	            	$order->save();
        		}
        	}
          // Notify Error
          Mage::helper('swisspostsales/Order')->addComment(
                $order,
                Mage::helper('core')->__(
                    'Send order return ERROR from Odoo: %s, debug: %s',
                    implode("\n", $result->getError()),
                    implode("\n", $result->getDebug())
                )
            );
            
            Mage::helper('swisspostsales/Order')->Notify(
                Mage::helper('core')->__('Send order #%s return ERROR', $order->getIncrementId()),
                Mage::helper('core')->__(
                    'ERROR: %s
                DEBUG: %s',
                    implode("\n", $result->getError()),
                    implode("\n", $result->getDebug())
                )
                , $error = true
            );
            
        }

        $observer->setData('order', $order);
    }
    
    /**
     * Receive payment status returned from the API
     *
     * @param Varien_Event_Observer $observer
     */
    function SwissPostApiGetPaymentStatus(Varien_Event_Observer $observer){
      $result = $observer->getData('result');
      $order_refs = $observer->getData('order_refs');
      if($result && $result->getValues()){
        $values = $result->getValues();
        foreach ($order_refs as $order_ref){
          foreach ($values as $status){
            if(stripos($status['order_ref'], $order_ref) !== false){   
              $order = Mage::helper('swisspostsales/Order')->__fromOrderRef($order_ref);
              if($order){
                Mage::helper('swisspostsales/Order')->processPaymentStatus($order, $status);
                // set final status
                if(Mage::helper('swisspostsales/Order')->isCompleted($order)){
                  Mage::helper('swisspostsales/Order')->__toCompleted($order);
                }
              }
            }
          }
        }
      }
    }
    /**
     * Receive transfer status returned from the API
     *
     * @param Varien_Event_Observer $observer
     */
    function SwissPostApiGetTransferStatus(Varien_Event_Observer $observer){
      $result = $observer->getData('result');
      $order_refs = $observer->getData('order_refs');
      if($result && $result->getValues()){
        $values = $result->getValues();
        foreach ($order_refs as $order_ref){
          foreach ($values as $status){
            if(stripos($status['order_ref'], $order_ref) !== false){   
              $order = Mage::helper('swisspostsales/Order')->__fromOrderRef($order_ref);
              if($order){
                Mage::helper('swisspostsales/Order')->processTransferStatus($order, $status);
                // set final status
                if(!Mage::helper('swisspostsales/Order')->isCompleted($order)){
                  Mage::helper('swisspostsales/Order')->__toCompleted($order);
                }
              }
            }
          }
        }
      }
    }
}
