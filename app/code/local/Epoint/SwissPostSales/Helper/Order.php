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

    const XML_CONFIG_PATH_FROM_AUTOINCREMENT = 'swisspost_api/order/from_autoincrement';

    const XML_CONFIG_PATH_CRON_LIMIT = 'swisspost_api/order/cron_limit';

    const ENABLE_CONVERT_ORDER_TO_INVOICE_AUTOMATICALLY_CONFIG_PATH = 'swisspost_api/invoice/enable_invoice_automatically';
    const ENABLE_CONVERT_ORDER_TO_INVOICE_PAYMENT_CODES_CONFIG_PATH = 'swisspost_api/invoice/automaticaly_invoice_by_payment_codes';


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
            if (isset($mapping[$code])) {
                $sale_order_values['delivery_method'] = $mapping[$code];
            }
        }
        // Attach payment method
        if (!isset($sale_order_values['payment_method']) || !$sale_order_values['payment_method']) {
            $sale_order_values['payment_method'] = Mage::getStoreConfig(self::XML_CONFIG_PATH_PAYMENT_DEFAULT);
            $mapping = Mage::helper('swisspost_api')->extractDefaultValues(self::XML_CONFIG_PATH_PAYMENT_MAPPING);
            $code = strtolower($order->getPayment()->getMethodInstance()->getCode());
            if (isset($mapping[$code])) {
                $sale_order_values['payment_method'] = $mapping[$code];
            }
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
                Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder())
                    ->save();

                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
                $order->save();
                self::addComment($order, 'The invoice has been create from SiwssPost Module');

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
        // Autoincrement limit
        if ($order->getIncrementId() <= (int)Mage::getStoreConfig(
                Epoint_SwissPostSales_Helper_Order::XML_CONFIG_PATH_FROM_AUTOINCREMENT
            )
        ) {
            return false;
        }

        return true;
    }
}