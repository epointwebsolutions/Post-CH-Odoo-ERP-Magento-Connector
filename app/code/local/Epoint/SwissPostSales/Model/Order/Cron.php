<?php

class Epoint_SwissPostSales_Model_Order_Cron
{

    const ENABLE_SEND_ORDER_CONFIG_PATH = 'swisspost_api/order/enable_cron';

    /**
     * Cron send orders to odoo
     */
    public function send()
    {
        if (!Mage::getStoreConfig(self::ENABLE_SEND_ORDER_CONFIG_PATH)) {
            return;
        }
        // All orders without odoo code id
        $order_collection = Mage::getModel('sales/order')
            ->getCollection()
            // Join invoices, send only 
            ->join(array('invoice' => 'sales/invoice'), 
              'invoice.order_id=main_table.entity_id', 
            array('invoice_entity_id'=>'entity_id'), null , 'left')
            ->addAttributeToFilter(
                'main_table.increment_id',
                 array('gt' => (int)Mage::getStoreConfig(Epoint_SwissPostSales_Helper_Order::XML_CONFIG_PATH_FROM_AUTOINCREMENT))
            )
            ->addAttributeToFilter(
                'main_table.'.Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID,
                array(array('null' => true),
                      array('eq' => 0)
                )
            )
            ->addAttributeToFilter(
                'main_table.'.Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_TIMESTAMP,
                array(array('null' => true),
                      array('eq' => 0)
                )
            );
            
        // Add Limit    
        $order_collection->getSelect()->limit((int)Mage::getStoreConfig(Epoint_SwissPostSales_Helper_Order::XML_CONFIG_PATH_CRON_LIMIT));    
        foreach ($order_collection as $order_item) {
            $order = Mage::getModel('sales/order')->load($order_item->getId());
            /**
            * Convert to invoice
            */
            if (Mage::getStoreConfig(Epoint_SwissPostSales_Helper_Order::ENABLE_CONVERT_ORDER_TO_INVOICE_AUTOMATICALLY_CONFIG_PATH)) {
              if($order->canInvoice()){
                  Mage::helper('swisspostsales/Order')->__toInvoice($order);
              }
            }
            Mage::helper('swisspost_api/Order')->createSaleOrder($order);
        }
    }

    /**
     * Receive orders to odoo
     */
    public function receive()
    {
        if (!Mage::getStoreConfig(self::ENABLE_SEND_ORDER_CONFIG_PATH)) {
            return;
        }
        // All orders without odoo code id
        $order_collection = Mage::getModel('sales/order')
            ->getCollection()
            ->addAttributeToFilter(
                Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID,
                array(array('null' => false),
                      array('gt' => 0)
                )
            );
        $order_refs = array();
        foreach ($order_collection as $order_item) {
            $order_refs[] = $order_item->getId();
        }
        if ($order_refs) {
            Mage::helper('swisspost_api/Order')->getPaymentStatus($order_refs);
        }
    }
}
