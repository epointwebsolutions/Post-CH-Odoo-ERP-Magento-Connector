<?php

class Epoint_SwissPostSales_Model_Order_Cron
{

  const ENABLE_SEND_ORDER_CONFIG_PATH = 'swisspost_api/order/enable_cron';
  const ENABLE_SEND_ORDER_STATUS_CONFIG_PATH = 'swisspost_api/order/status';

  /**
   * Cron send orders to odoo
   * - orders with status "pending": -> transmit to Odoo, set to "complete"
   * - orders with processing, older than 10 min with invoice: -> transmit to Odoo, set to "complete"
   * - orders with processing, older than 10 min. and without invoice: -> set to "cancelled"
   *
   */
  public function send()
  {
    if (!Mage::getStoreConfig(Epoint_SwissPostSales_Helper_Order::ENABLE_SEND_ORDER_CONFIG_PATH)) {
      return;
    }

    // Timebased workflow?
    if (Mage::getStoreConfig(Epoint_SwissPostSales_Helper_Order::ENABLE_TIMEBASED_WORKFLOW)) {
      /**
       * Orders with status "pending": -> transmit to Odoo, set to "complete"
       */
      $orders_to_be_sent = $this->__getOrderCollection(array(
        Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
      ),
        $invoiced = 0);
      if ($orders_to_be_sent) {
        $this->__sendOrderCollection($orders_to_be_sent);
      }
      /**
       *  - orders with processing, older than 10 min with invoice: -> transmit to Odoo, set to "complete"
       */
      $to_date = date('Y-m-d H:i:s', time() - Mage::getStoreConfig(
        Epoint_SwissPostSales_Helper_Order::TIMEBASED_WORKFLOW_SECONDS)
      );
      $orders_to_be_sent = $this->__getOrderCollection(array(
        Mage_Sales_Model_Order::STATE_PROCESSING
      ),
        $invoiced = 1,
        $from_date = '',
        $to_date
      );
      if ($orders_to_be_sent) {
        $this->__sendOrderCollection($orders_to_be_sent);
      }
      /*
       * - orders with processing, older than 10 min. and without invoice: -> set to "cancelled"
       */
      $orders_to_be_cancelled = $this->__getOrderCollection(array(
        Mage_Sales_Model_Order::STATE_PROCESSING
      ),
        $invoiced = -1,
        $from_date = '',
        $to_date
      );
      if ($orders_to_be_cancelled) {
        foreach ($orders_to_be_cancelled as $order_item) {
          $order = Mage::getModel('sales/order')->load($order_item->getId());
          // set conceled id
          $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, TRUE);
          $order->save();
          // Add comment
          Mage::helper('swisspostsales/Order')->addComment(
            $order,
            Mage::helper('core')->__('Set order cancelled.')
          );
          // Notify
          Mage::helper('swisspostsales/Order')->Notify(
            Mage::helper('core')->__(
              'Set order cancelled: #%s', $order->getIncrementId()
            ),
            ''
            , $error = FALSE
          );
        }
      }
    }else{
      // Standard behavior
      $orders_to_be_sent = $this->__getOrderCollection();
      if ($orders_to_be_sent) {
        $this->__sendOrderCollection($orders_to_be_sent);
      }
    }
  }

  /**
   * Orders with status "pending": -> transmit to Odoo, set to "complete".
   *
   * @param $status_filter array
   *   Status filter, array with statuses.
   *
   * @param $invoiced int
   *   Check if was invoiced: -1 was not invoiced!, 0 ignore invoice,
   * 1 was invoiced.
   *
   * @param $from_date date
   *   Created from certain date.
   *
   * @return $order_collection object
   *   Order collection.
   */
  private function __getOrderCollection(
    $status_filter = array(),
    $invoiced = 1,
    $from_date= '',
    $to_date = ''){
    // Filter status, if not defined, use default configuration.
    if(!$status_filter) {
      $status_filter = explode(',',
        Mage::getStoreConfig(self::ENABLE_SEND_ORDER_STATUS_CONFIG_PATH)
      );
      if (is_array($status_filter)) {
        $status_filter = array_map('trim', $status_filter);
      }
    }
    // From date filter
    if(!$from_date) {
      $from_date = date('Y-m-d H:i:s', strtotime(Mage::getStoreConfig(
          Epoint_SwissPostSales_Helper_Order::XML_CONFIG_PATH_FROM_DATE)
        )
      );
    }

    // All orders without odoo code id
    $order_collection = Mage::getModel('sales/order')
      ->getCollection();
    // Invoice condition
    if($invoiced == 1) {
      $attribute = Mage::getModel('eav/entity_attribute')
        ->loadByCode('invoice', 'order_id');
      if ($attribute) {
        $order_collection->getSelect()->join(
          array('invoice' => 'sales_order_entity_int'),
          'invoice.value = e.entity_id AND invoice.attribute_id=' . $attribute->getId()
          . ' AND invoice.entity_type_id=' . $attribute->getEntityTypeId(),
          array('invoice.entity_id AS invoice_id')
        );
      }
      // Was not invoiced.
    }elseif($invoiced == -1){
      $attribute = Mage::getModel('eav/entity_attribute')
        ->loadByCode('invoice', 'order_id');
      if ($attribute) {
        $order_collection->getSelect()->joinLeft(
          array('invoice' => 'sales_order_entity_int'),
          'invoice.value = e.entity_id AND invoice.attribute_id=' . $attribute->getId()
          . ' AND invoice.entity_type_id=' . $attribute->getEntityTypeId(),
          array('invoice.entity_id AS invoice_id')
        );
      }
    }
    // Add odoo attribute filter.
    $attribute_odoo = Mage::getModel('eav/entity_attribute')->loadByCode('order',
      Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID
    );
    $order_collection->getSelect()->joinLeft(
      array('odoo' =>  'sales_order_int'),
      'odoo.entity_id = e.entity_id AND odoo.attribute_id='.$attribute_odoo->getId()
      .' AND odoo.entity_type_id='.$attribute_odoo->getEntityTypeId(),
      array('odoo.value AS order_odoo_id')
    );
    if($from_date) {
      $order_collection
        ->addFieldToFilter(
          'created_at',
          array('gt' => $from_date)
        );
    }
    if($to_date){
      $order_collection
        ->addFieldToFilter(
          'created_at',
          array('lt' => $to_date)
        );
    }
    if($status_filter) {
      $order_collection->addFieldToFilter(
        'status',
        array('in' => $status_filter)
      );
    }
    // Odoo timestamp condition
    $order_collection->getSelect()->where(new Zend_Db_Expr(
        "(
              odoo.value = 0 OR 
              odoo.value IS NULL
            )"
      )
    );
    // Add invoiced condition.
    if($invoiced == -1){
      $order_collection->getSelect()->where(new Zend_Db_Expr(
          "(
              invoice.value = 0 OR 
              invoice.value IS NULL
            )"
        )
      );
    }
    $order_collection->getSelect()->group('e.entity_id');
    // Add limit from config.
    $order_collection->getSelect()->limit((int)Mage::getStoreConfig(Epoint_SwissPostSales_Helper_Order::XML_CONFIG_PATH_CRON_LIMIT));
    return $order_collection;
  }

  /**
   * Send api order collection
   * @param $order_collection
   */
  private function __sendOrderCollection($order_collection){
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
