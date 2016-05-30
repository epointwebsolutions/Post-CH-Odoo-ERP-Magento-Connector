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
        /**
         * Convert to invoice
         */
        if($order->canInvoice()){
          if (Mage::getStoreConfig(
              Epoint_SwissPostSales_Helper_Order::ENABLE_CONVERT_ORDER_TO_INVOICE_AUTOMATICALLY_CONFIG_PATH
          )) {
              // Check config, only if the order payment method is allowed to be converted to invoice.
              $codes = explode(",", strtolower(Mage::getStoreConfig(
                  Epoint_SwissPostSales_Helper_Order::ENABLE_CONVERT_ORDER_TO_INVOICE_PAYMENT_CODES_CONFIG_PATH
              )));
              $order_payment_code = strtolower($order->getPayment()->getMethodInstance()->getCode());
              foreach ($codes as $code){
                if($code == '*' || $code == $order_payment_code){
                  Mage::helper('swisspostsales/Order')->__toInvoice($order);
                }
              }
          }
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
        $order->setData(Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_TIMESTAMP, $now)
            ->getResource()->saveAttribute(
                $order, Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_TIMESTAMP
            );
        // Save connection.
        if ($result->getResult('odoo_id')) {
            $order->setData(
                Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID, $result->getResult('odoo_id')
            )
                ->getResource()->saveAttribute($order, Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID);
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
}
