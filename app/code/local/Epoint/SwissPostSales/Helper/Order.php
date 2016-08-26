<?php

/**
 * Data Helper
 *
 */
class Epoint_SwissPostSales_Helper_Order extends Mage_Core_Helper_Abstract
{
    /**
     * Setting path for mapping fields, default values
     */
    const XML_CONFIG_PATH_DEFAULT_VALUES = 'swisspost_api/order/default_values';
    /**
     * Comment prefix
     *
     */
    const XML_CONFIG_PATH_DEFAULT_COMMENT_PREFIX = 'swisspost_api/order/comment_prefix';

    const XML_CONFIG_PATH_PAYMENT_MAPPING = 'swisspost_api/order_payment_method/mapping';

    const XML_CONFIG_PATH_PAYMENT_DEFAULT = 'swisspost_api/order_payment_method/default';

    const XML_CONFIG_PATH_SHIPPING_MAPPING = 'swisspost_api/order_shipping_method/mapping';

    const XML_CONFIG_PATH_SHIPPING_DEFAULT = 'swisspost_api/order_shipping_method/default';

    const XML_CONFIG_PATH_DISCOUNT_SKU_MAPPING = 'swisspost_api/discount/mapping2product';

    const XML_CONFIG_PATH_NOTIFY_FAILURE_EMAILS = 'swisspost_api/notification/failure_emails';

    const XML_CONFIG_PATH_NOTIFY_SUCCESS_EMAILS = 'swisspost_api/notification/success_emails';

    const XML_CONFIG_PATH_FROM_DATE = 'swisspost_api/order/from_date';

    const XML_CONFIG_PATH_CRON_LIMIT = 'swisspost_api/order/cron_limit';

    
    const ENABLE_CONVERT_ORDER_TO_INVOICE_AUTOMATICALLY_CONFIG_PATH = 'swisspost_api/invoice/enable_invoice_automatically';
    const ENABLE_CONVERT_ORDER_TO_INVOICE_PAYMENT_CODES_CONFIG_PATH = 'swisspost_api/invoice/automaticaly_invoice_by_payment_codes';

    const ENABLE_USE_API_PDF_INVOICE_CONFIG_PATH = 'swisspost_api/invoice/enable_invoice_print';
    
    const ENABLE_SEND_ORDER_CONFIG_PATH = 'swisspost_api/order/enable_cron';

    /**
     * Get all discounts sku
     *
     * @return array
     */
    public static function getDiscountSKUs()
    {
        static $skus;
        if (isset($skus)) {
            return $skus;
        }
        $skus = array();
        // Magento sku|magento shipping method
        $mapping = Mage::helper('swisspost_api')->extractDefaultValues(self::XML_CONFIG_PATH_DISCOUNT_SKU_MAPPING);
        foreach ($mapping as $sku => $magentoDiscountRuleId) {
            $skus[$magentoDiscountRuleId] = $sku;
        }

        return $skus;
    }

    /**
     * Get SwissPost Discount rule id
     *
     * @param $magentoDiscountRuleId
     *
     * @return array|int
     */
    public static function __toSwisspostDiscount($magentoDiscountRuleId)
    {
        $skus = self::getDiscountSKUs();
        $catalogRule = 'catalog:' . $magentoDiscountRuleId;
        $shoppingCartRule = 'shoppingcart:' . $magentoDiscountRuleId;
        if (isset($skus[$catalogRule])) {
            return $skus[$catalogRule];
        }
        if (isset($skus[$shoppingCartRule])) {
            return $skus[$shoppingCartRule];
        }

        return 0;
    }

    /**
     * Implement methods to convert magento order to swisspost array
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return string
     */
    public function __toSwissPost(Mage_Sales_Model_Order $order)
    {
        $sale_order_values['note'] = '';
        $mapping = Mage::helper('swisspost_api')->getMapping('order');
        $sale_order_values = Mage::helper('swisspost_api')->__toSwissPost($order, $mapping);
        // Special case
        if(isset($mapping['order_ref']) && $mapping['order_ref'] == 'increment_id'){
       		$sale_order_values['order_ref'] = $order->getIncrementId();
        }
        // Add transaction id
       	$sale_order_values['transaction_id'] = $order->getPayment()->getLastTransId();
        // Attach default values
        $default_values = Mage::helper('swisspost_api')->extractDefaultValues(self::XML_CONFIG_PATH_DEFAULT_VALUES);
        foreach ($default_values as $key => $value) {
            $sale_order_values[$key] = $value;
        }
        // Attach delivery method
        if (!isset($sale_order_values['delivery_method']) || !$sale_order_values['delivery_method']) {
            $sale_order_values['delivery_method'] = Mage::getStoreConfig(self::XML_CONFIG_PATH_SHIPPING_DEFAULT);
            $mapping = Mage::helper('swisspost_api')->extractDefaultValues(self::XML_CONFIG_PATH_SHIPPING_MAPPING);
            $code = strtolower($order->getShippingMethod());
            foreach ($mapping as $candidateCode=>$odooDeliveryMethodCode){
            	if(strtolower($candidateCode) == $code){
            		$sale_order_values['delivery_method'] = $odooDeliveryMethodCode;	
            		break;
            	}
            }
        }  
        // Attach payment method
        if (!isset($sale_order_values['payment_method']) || !$sale_order_values['payment_method']) {
          $sale_order_values['payment_method'] = self::__toPaymentMethod($order);
        }
        // Date length is 10;
        if (!isset($sale_order_values['date_order'])) {
            $sale_order_values = $order->getCreatedAt();
        }
        $sale_order_values['date_order'] = substr($sale_order_values['date_order'], 0, 10);
        // Attach order last comment on order note
        if ($order->getCustomerNote()) {
            $sale_order_values['note'] = $order->getCustomerNote();
        } else {
            $comments = $order->getAllStatusHistory();
            foreach ($comments as $comment) {
                if ($comment->getData('entity_name') != 'admin_comment') {
                    continue;
                }
                $text = $comment->getData('comment');
                if (trim($text)) {
                    $sale_order_values['note'] = trim($text);
                }
            }
        }

        return $sale_order_values;
    }

    /**
     * Implement methods that return order from a order ref
     *
     * @param  string $order_ref
     *
     * @return $order Mage_Sales_Model_Order or null
     */
    public function __fromOrderRef($order_ref)
    {
        $mapping = Mage::helper('swisspost_api')->getMapping('order');
        $order = null;
        
        switch ($mapping['order_ref']){
          case 'increment_id':
               $order = Mage::getModel('sales/order')->load($order_ref, 'increment_id');
            break;          
            case 'entity_id':
               $order = Mage::getModel('sales/order')->load($order_ref);
            break;
        }
        return $order;
    }
    /**
     * Implement methods that return order_ref from an ordeer
     *
     * @param  Mage_Sales_Model_Order $order
     *
     * @return string $order_ref
     */
    public function __toOrderRef(Mage_Sales_Model_Order $order)
    {
        $mapping = Mage::helper('swisspost_api')->getMapping('order');
        $sale_order_values = Mage::helper('swisspost_api')->__toSwissPost($order, $mapping);
        return $sale_order_values['order_ref'];
       
    }
    /**
     * Get discounts order rule, corresponding to SiwssPost rules
     *
     * @param $item
     *
     * @return array
     */
    public static function getDiscountsItem($item)
    {
        $rules = array();
        //if the item has not had a rule applied to it skip it
        if ($item->getAppliedRuleIds() == '') {
            return $rules;
        }

        foreach (explode(",", $item->getAppliedRuleIds()) as $ruleID) {
            $rule = self::__toSwisspostDiscount($ruleID);
            if ($rule) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    /**
     * generate invoice from an order
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return boolean
     */
    public static function __toInvoice(Mage_Sales_Model_Order $order)
    {
        try {
            if ($order->canInvoice()) {

                $invoice = $order->prepareInvoice();
                $invoice->register();
                $invoice->setState(Mage_Sales_Model_Order_Invoice::STATE_OPEN);
                Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder())
                    ->save();
                self::addComment($order, Mage::helper('core')->__('The invoice has been create from'
                    .' Magento-Odoo Integration module'));
                return true;
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return false;
    }

    /**
     * Add Comment
     */
    public static function addComment(Mage_Sales_Model_Order $order, $comment = '')
    {
        $prefix = Mage::getStoreConfig(self::XML_CONFIG_PATH_DEFAULT_COMMENT_PREFIX);
        if ($prefix) {
            $comment = $prefix . "\n" . $comment;
        }
        $order->addStatusHistoryComment($comment)
            ->setIsVisibleOnFront(false)
            ->setIsCustomerNotified(false);
        $order->save();
    }


    /**
     * Notify email
     *
     * @param      $message
     * @param null $logLevel
     */
    public static function Notify($subject, $message, $error = true)
    {
        if ($error) {
            $emails = explode(',', Mage::getStoreConfig(self::XML_CONFIG_PATH_NOTIFY_FAILURE_EMAILS));
        } else {
            $emails = explode(',', Mage::getStoreConfig(self::XML_CONFIG_PATH_NOTIFY_SUCCESS_EMAILS));
        }
        if ($emails) {
            foreach ($emails as $email) {
                Mage::helper('swisspost_api/MailLog')->sendmail($email, $subject, $message);
            }
        }
    }

    /**
     * Check if order can be send
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return unknown
     */
    public static function canSend(Mage_Sales_Model_Order $order)
    {
        // Has bee sent already
        if (Epoint_SwissPostSales_Helper_Data::OrderHasBeenProcessed($order)) {
            return false;
        }
        if(!Mage::getStoreConfig(
                Epoint_SwissPostSales_Helper_Order::XML_CONFIG_PATH_FROM_DATE 
            )){
        	Mage::helper('swisspost_api')->log(
                      Mage::helper('core')->__('Error on check send order, from date filter is not configured: %s, odoo id: %s', 
                      $order->getId(), 
                      $order->getData(Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID)
                     )
                 );
                 return false;
        }
        $from_date = date('Y-m-d H:i:s', strtotime(Mage::getStoreConfig(
         	Epoint_SwissPostSales_Helper_Order::XML_CONFIG_PATH_FROM_DATE)
         	)
        );
        // Date limit
        if ($order->getCreatedAt() < $from_date ) {
            return false;
        }

        return true;
    }
    /**
     * get pdf object for the
     *
     * @param invoice $invoice
     */
    public function getPdf(Mage_Sales_Model_Order_Invoice $invoice){
    	$pdf = null;
        $order = $invoice->getOrder();
        $sales_order_values = Mage::helper('swisspostsales/Order')->__toSwissPost($order);
        $result = Mage::helper('swisspost_api/Order')->getInvoiceDocs($sales_order_values['order_ref']);
        $documents = $result->getResult('values');
        foreach ($documents as $document){
        	$content = base64_decode($document['content']);
        	if($content){
          		$pdf = Zend_Pdf::parse($content);
          		return $pdf;
        	}
		}
	    return $pdf;
    }
    /**
     * Check if can invoice an order
     *
     */
    public function canInvoice($order){
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
               return true;
              }
            }
        }
      }
      return false;
    }
    /**
     * Set invoice status to payment
     * 
     * @param Mage_Sales_Model_Order $order
     * @param array $status
     */
    public function processPaymentStatus(Mage_Sales_Model_Order $order, $status){
      if($status['state']){
        switch ($status['state']){
          case 'paid':
            // create invoice it was not created...
            if(Mage::helper('swisspostsales/Order')->canInvoice($order)){
              Mage::helper('swisspostsales/Order')->__toInvoice($order);
            }
            // Change invoice status to PAID if it is opened
            $invoice = $order->getInvoiceCollection()
              ->addAttributeToSort('created_at', 'DSC')
              ->setPage(1, 1)
              ->getFirstItem();
            // Set paid  
            if($invoice->getState() == Mage_Sales_Model_Order_Invoice::STATE_OPEN){
              $invoice->setState(Mage_Sales_Model_Order_Invoice::STATE_PAID);
              $invoice->save();
            }
         }
      }
    }
    
    /**
     * Handle API process status
     *
     * @param Mage_Sales_Model_Order $order
     * @param array $status
     */
    public function processTransferStatus(Mage_Sales_Model_Order $order, $status){
      if($status['state']){
      	  switch ($status['state']){
      	    case 'done':
              // create invoice it was not created...
              if($this->canInvoice($order)){
                $this->__toInvoice($order);
              }
              // Change invoice status to PAID if it is opened
              $shipment = $order->getShipmentsCollection()
                ->addAttributeToSort('created_at', 'DSC')
                ->setPage(1, 1)
                ->getFirstItem();
              // Set shipment 
              if(!$shipment->getId()){
                $shipment = $this->__toShipment($order);
              }
              // attach tracking info
              if($shipment && $shipment->getId()){
                Mage::helper('swisspostsales/Shipment')->setTrackingNumber($order, $shipment, $status);
              }
              break;
          }
      }
    }
    
    /**
     * Create shipment, 
     *
     * @param Mage_Sales_Model_Order $order
     */
    public function __toShipment(Mage_Sales_Model_Order $order, $trackingInfo){
      $shipment = null;
      try {
        // Create Qty array
        $shipmentItems = array();
        foreach ($order->getAllItems() as $item) {
          $shipmentItems [$item->getId()] = $item->getQtyToShip();
        }
        // Prepear shipment and save ....
        if ($order->getId() && !empty($shipmentItems) && $order->canShip()) {
          $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($shipmentItems);
          $shipment->save();
        }
      }catch (Exception $e){
         Mage::helper('swisspost_api')->LogException($e);
         Mage::helper('swisspost_api')->log(
                      Mage::helper('core')->__('Error on create shipment to order: %s, odoo id: %s', 
                      $order->getId(), 
                      $order->getData(Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID)
                     )
                 );
      }
      return $shipment;
  }
  /**
   * Check if an order is connected
   *
   * @param Mage_Sales_Model_Order $order
   * @return boolean
   */
  public static function isConnected(Mage_Sales_Model_Order $order){
    if($order->getData(Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID)){
      return true;
    }
    return false;
  }
  /**
   * Check if an order is completed
   *
   * @param Mage_Sales_Model_Order $order
   */
  public static function isCompleted(Mage_Sales_Model_Order $order){
     // Change invoice status to PAID if it is opened
     $invoices = $order->getInvoiceCollection();
     $paid = false;
     $shipped = false;
     foreach ($invoices as $invoice){
       if($invoice->getState() == Mage_Sales_Model_Order_Invoice::STATE_PAID){
         $paid = true;
         break;
       }
     }
     if($paid){
       // Exists one shipment?
       $shipments = $order->getShipmentsCollection();
       foreach ($shipments as $shipment){
         $shipped = true;
         break;
       }
       if($shipped){
         return true;
       }
     }
     return false;
  }
  
  /**
   * Check if an order is completed
   *
   * @param Mage_Sales_Model_Order $order
   */
  public static function __toCompleted(Mage_Sales_Model_Order $order){
    if($order->getStatus() != Mage_Sales_Model_Order::STATE_COMPLETE){
        $order->setState('state', Mage_Sales_Model_Order::STATE_COMPLETE, TRUE);
        $order->setStatus('complete');
        $order->save();
        // Add comment    
        Mage::helper('swisspostsales/Order')->addComment(
              $order,
              Mage::helper('core')->__('Set order status completed')
          );
    }
  }
  /**
   * Get payment method for oddo
   * @param $order
   * @return string $method
   */
  static function __toPaymentMethod(Mage_Sales_Model_Order $order){
      $method = '';
      $payment_mapping = Mage::helper('swisspost_api')->extractDefaultValues(self::XML_CONFIG_PATH_PAYMENT_MAPPING);
      $code = strtolower($order->getPayment()->getMethodInstance()->getCode());
      $cc_name = strtolower($order->getPayment()->getCcType());
      /**
       * check cc card type
       */
      // special case
      if($cc_name){
         $code_with_cc = $code.'[cc_type:'.$cc_name.']';
         foreach ($payment_mapping as $candidateCode=>$odooPaymentMethodCode){
        	if(strtolower($candidateCode) == $code_with_cc){
        		$method = $odooPaymentMethodCode;
        		break;
        	}
        }
      }
      if(!$method){
        if($payment_mapping){
          foreach ($payment_mapping as $candidateCode=>$odooPaymentMethodCode){
          	if(strtolower($candidateCode) == $code){
          		$method = $odooPaymentMethodCode;	
          		break;
          	}
          }
        }
      }
      if(!$method){
        $method = Mage::getStoreConfig(self::XML_CONFIG_PATH_PAYMENT_DEFAULT);
      }
      return $method;
  }
}
