<?php

class Epoint_SwissPostSales_Model_Order_Cron
{
	/**
	 * Configuration path for enabling/disabling cron
	 *
	 */
    const ENABLE_SEND_ORDER_CONFIG_PATH = 'swisspost_api/order/enable_cron';
    const ENABLE_SEND_ORDER_STATUS_CONFIG_PATH = 'swisspost_api/order/status';
	
    /**
     * Cron send orders to odoo
     */
    public function send()
    {
        if (!Mage::getStoreConfig(Epoint_SwissPostSales_Helper_Order::ENABLE_SEND_ORDER_CONFIG_PATH)) {
            return;
        }
        // Check if the date is configured.
        if(!Mage::getStoreConfig(
                Epoint_SwissPostSales_Helper_Order::XML_CONFIG_PATH_FROM_DATE 
            )){
        	Mage::helper('swisspost_api')->log(
                      Mage::helper('core')->__('Error on send order cron, from date filter is not configured!')
                 );
           return ;      
        }
        $status_filter = explode(',', 
        	Mage::getStoreConfig(self::ENABLE_SEND_ORDER_STATUS_CONFIG_PATH)
        );
        if(is_array($status_filter)){
        	$status_filter = array_map('trim', $status_filter);
        }	
        $from_date = date('Y-m-d H:i:s', strtotime(Mage::getStoreConfig(
                 Epoint_SwissPostSales_Helper_Order::XML_CONFIG_PATH_FROM_DATE)
                 )
               );
        // All orders without odoo code id
        $order_collection = Mage::getModel('sales/order')
            ->getCollection()
            // Join invoices, send only 
            ->join(array('invoice' => 'sales/invoice'), 
              'invoice.order_id=main_table.entity_id', 
            array('invoice_entity_id'=>'entity_id'), null , 'left')
            ->addAttributeToFilter(
                'main_table.created_at',
                 array('gt' => $from_date)
            )
            ->addFieldToFilter(
                'main_table.status',
                 array(array('in'=>array($status_filter)))
            )
            ->addAttributeToFilter(
                'main_table.'.Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID,
                array(array('null' => true),
                      array('eq' => 0)
                )
        );
        // Add group    
        $order_collection->getSelect()->group('main_table.entity_id');
        // Add Limit    
        $order_collection->getSelect()->limit((int)Mage::getStoreConfig(Epoint_SwissPostSales_Helper_Order::XML_CONFIG_PATH_CRON_LIMIT));    
        foreach ($order_collection as $order_item) {
            $order = Mage::getModel('sales/order')->load($order_item->getId());
            // check if can be sent again
            if(Mage::helper('swisspostsales/Order')->isConnected($order)){
            	 Mage::helper('swisspost_api')->log(
                        Mage::helper('core')->__('Stop sending again order: %s, odoo id: %s', $order->getId(), 
                        $order->getData(Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID)
                        )
                   );
            	continue;
            }
            // The order was not invoiced ???
            if(!$order->getInvoiceCollection()){
            	Mage::helper('swisspost_api')->log(
                        Mage::helper('core')->__('Stop sending again order, no invoice: %s', $order->getId()
                        )
                    );
            	continue;
            }
            Mage::helper('swisspost_api/Order')->createSaleOrder($order);
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus()
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
                Mage_Sales_Model_Order::STATE_PROCESSING
           );
         // Add group    
        $order_collection->getSelect()->group('main_table.entity_id');
        $order_refs = array();
        foreach ($order_collection as $order_item) {
            $order_refs[] = "".Mage::helper('swisspostsales/Order')->__toOrderRef($order_item);
        }
        if ($order_refs) {
            Mage::helper('swisspost_api/Order')->getPaymentStatus($order_refs);
        }
    }
}
