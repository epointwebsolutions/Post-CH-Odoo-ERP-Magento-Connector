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
            ->addAttributeToFilter(
                Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID,
                array(array('null' => true),
                      array('eq' => 0)
                )
            )
            ->addAttributeToFilter(
                Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_TIMESTAMP,
                array(array('null' => true),
                      array('eq' => 0)
                )
            );
        foreach ($order_collection as $order_item) {
            $order = Mage::getModel('sales/order')->load($order_item->getId());
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
