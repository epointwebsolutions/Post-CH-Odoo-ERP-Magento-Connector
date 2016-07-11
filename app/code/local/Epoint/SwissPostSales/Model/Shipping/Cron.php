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
            ->getCollection()
            // Join invoice
            ->join(array('invoice' => 'sales/invoice'), 
              'invoice.order_id=main_table.entity_id', 
            array('invoice_entity_id'=>'entity_id'), null , 'left')
              ->addAttributeToFilter(
                'main_table.created_at',
                 array('gt' => $from_date)
            )            
            // Filter payment status
            ->addAttributeToFilter(
                'invoice.state',
                array(Mage_Sales_Model_Order_Invoice::STATE_PAID)
            )// Filter odoo id
            ->addAttributeToFilter(
                Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID,
               array(
                      array('gt' => 0)
                )
            )
           ->addFieldToFilter(
                'main_table.status',
                'processing'
           );
         // Add group    
        $order_collection->getSelect()->group('main_table.entity_id');
        $order_refs = array();
        foreach ($order_collection as $order_item) {
            $order_refs[] = "".Mage::helper('swisspostsales/Order')->__toOrderRef($order_item);
        }
        if ($order_refs) {
            Mage::helper('swisspost_api/Order')->getTransferStatus($order_refs);
        }
    }
}
