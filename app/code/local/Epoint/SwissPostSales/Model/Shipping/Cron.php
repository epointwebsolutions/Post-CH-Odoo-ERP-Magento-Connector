<?php

class Epoint_SwissPostSales_Model_Shipping_Cron
{

    /**
     * Cron receive shipping products and inform system about them
     */
    public function getTransferStatus()
    {
        $from_date = date('Y-m-d H:i:s', strtotime(Mage::getStoreConfig(
            Epoint_SwissPostSales_Helper_Order::XML_CONFIG_PATH_FROM_DATE)
          )
        );
        // All orders without odoo code id
        $order_collection = Mage::getModel('sales/order')
          ->getCollection();
        $order_collection->addFieldToFilter(
          ''.Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID,
          array('gt' => 0)
        );

        $order_collection->addFieldToFilter(
          'status',
          Mage_Sales_Model_Order::STATE_PROCESSING
        );
        // Add group
        $order_collection
          ->addAttributeToFilter(
            'created_at',
            array('gt' => $from_date)
          );

        // Add group
        $order_refs = array();
        foreach ($order_collection as $order_item) {
            $order_refs[] = "".Mage::helper('swisspostsales/Order')->__toOrderRef($order_item);
        }
        if ($order_refs) {
            Mage::helper('swisspost_api/Order')->getTransferStatus($order_refs);
        }
    }
}
