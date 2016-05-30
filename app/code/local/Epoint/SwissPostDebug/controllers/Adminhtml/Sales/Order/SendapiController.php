<?php
include_once('Mage/Adminhtml/controllers/Sales/OrderController.php');

class Epoint_SwissPostDebug_Adminhtml_Sales_Order_SendapiController extends Mage_Adminhtml_Sales_OrderController
{

    /**
     * Order action
     */
    public function orderAction()
    {

        $order = $this->_initOrder();
        try {
           /**
            * Convert to invoice
            */
            if (Mage::getStoreConfig(Epoint_SwissPostSales_Helper_Order::ENABLE_CONVERT_ORDER_TO_INVOICE_AUTOMATICALLY_CONFIG_PATH)) {
              if($order->canInvoice()){
                  Mage::helper('swisspostsales/Order')->__toInvoice($order);
              }
            }
            $result = Mage::helper('swisspost_api/Order')->createSaleOrder($order);
            if ($order->getData('odoo_id') > 0 && $order->getData('odoo_id') == $result->getResult('odoo_id')) {
                $this->_getSession()->addSuccess(Mage::helper('core')->__('The order has been sent to API.'));
                $this->_getSession()->addSuccess(
                    Mage::helper('core')->__('The order  ID on API is: %s', $order->getData('odoo_id'))
                );
            } else {
                throw new Exception(print_r($result->getDebug(), 1));
            }

        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('Failed to import the order: %s.', $e->getMessage()));
            Mage::logException($e);
        }
        $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
    }
}