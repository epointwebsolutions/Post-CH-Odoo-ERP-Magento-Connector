<?php
include_once('Mage/Adminhtml/controllers/Sales/OrderController.php');

class Epoint_SwissPostDebug_Adminhtml_Sales_Order_GetstatusController extends Mage_Adminhtml_Sales_OrderController
{

    /**
     * Order action
     */
    public function paymentAction()
    {

        $order = $this->_initOrder();
        try {
        	  $order_ref = Mage::helper('swisspostsales/Order')->__toOrderRef($order);
            $result = Mage::helper('swisspost_api/Order')->getPaymentStatus(array($order_ref));
            if ($result->isOk()) {
            	foreach ($result->getValues() as $value){
	                $this->_getSession()->addSuccess(
	                    Mage::helper('core')->__('The payment status for order ID %s is: %s', $value['order_ref'], $value['state'])
	                );
            	}
            } else {
                throw new Exception(implode("\n", $result->getError()));
            }

        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('Failed to get status for the order: %s.', $e->getMessage()));
            Mage::logException($e);
        }
        $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
    }
    /**
     * Order action
     */
    public function transferAction()
    {
        $order = $this->_initOrder();
        try {
        	  $order_ref = Mage::helper('swisspostsales/Order')->__toOrderRef($order);
            $result = Mage::helper('swisspost_api/Order')->getTransferStatus(array($order_ref));
            if ($result->isOk()) {
            	foreach ($result->getValues() as $value){
	                $this->_getSession()->addSuccess(
	                    Mage::helper('core')->__('The transfer status for order ID %s is: %s', $value['order_ref'], $value['state'])
	                );
            	}
            } else {
                throw new Exception(implode("\n", $result->getError()));
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('Failed to get status for the order: %s.', $e->getMessage()));
            Mage::logException($e);
        }
        $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
    }
}