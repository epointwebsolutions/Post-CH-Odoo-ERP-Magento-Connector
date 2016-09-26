<?php

class Epoint_SwissPostSales_Model_Order_Cron
{

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

          ->getCollection();
        // Odoo connection first condition.
        $attribute = Mage::getModel('eav/entity_attribute')->loadByCode('invoice', 'order_id');
        if($attribute){
            $order_collection->getSelect()->join(
              array('invoice' =>  'sales_order_entity_int'),
              'invoice.entity_id = e.entity_id AND invoice.attribute_id='.$attribute->getId()
              .' AND invoice.entity_type_id='.$attribute->getEntityTypeId(),
              array('invoice.value AS invoice_id')
            );
        }
        $attribute_odoo = Mage::getModel('eav/entity_attribute')->loadByCode('order',
          Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID
        );
        $order_collection->getSelect()->joinLeft(
          array('odoo' =>  'sales_order_int'),
          'odoo.entity_id = e.entity_id AND odoo.attribute_id='.$attribute_odoo->getId()
          .' AND odoo.entity_type_id='.$attribute_odoo->getEntityTypeId(),
          array('odoo.value AS order_odoo_id')
        );

        $order_collection
          ->addFieldToFilter(
            'created_at',
            array('gt' => $from_date)
          )
          ->addFieldToFilter(
            'status',
            array('in'=>$status_filter)
          )
          ->addFieldToFilter(
            'status',
            array('in'=>$status_filter)
          );

        // Odoo odoo timestamp condition
        $order_collection->getSelect()->where(new Zend_Db_Expr(
            "(
              odoo.value = 0 OR 
              odoo.value IS NULL
            )"
          )
        );
        $order_collection->getSelect()->group('e.entity_id');
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
          ->getCollection();
        // Odoo connection first condition.
        $order_collection->getSelect()->where(new Zend_Db_Expr(
            "(
          ".Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID." > 0
          )"
          )
        );

        $order_collection->addFieldToFilter(
          'status',
          Mage_Sales_Model_Order::STATE_PROCESSING
        );
        $order_collection
          ->addAttributeToFilter(
            'created_at',
            array('gt' => $from_date)
          );
        $order_refs = array();
        foreach ($order_collection as $order_item) {
            $order_refs[] = "".Mage::helper('swisspostsales/Order')->__toOrderRef($order_item);
        }
        if ($order_refs) {
            Mage::helper('swisspost_api/Order')->getPaymentStatus($order_refs);
        }
    }
}
