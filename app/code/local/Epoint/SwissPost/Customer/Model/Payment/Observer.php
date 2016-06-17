<?php
/**
 * Implement Payment Observer
 *
 */
class Epoint_SwissPost_Customer_Model_Payment_Observer
{   
    /**
     * Config paths
     *
     */
    const ENABLE_PAYMENT_CHECK_ANONYMOUS_CFG_PATH = 'swisspost_api/payment/enable_anonymous';
    const ENABLE_PAYMENT_ANONYMOUS_LIMITS_CFG_PATH = 'swisspost_api/payment/anonymous_limits';
    const ENABLE_PAYMENT_ENABLE_CHECK_CREDIT_CFG_PATH = 'swisspost_api/payment/enable_check_credit';
    const ENABLE_PAYMENT_METHODS_CREDIT_CFG_PATH = 'swisspost_api/payment/check_credit_methods';
    
    /**
     * Check if a payment method is active
     *
     * @param object $observer
     */
    public function isActive(Varien_Event_Observer $observer)
    {   
      
        // Payment method
        $instance = $observer->getMethodInstance();
        // Get code
        $check_method_code = strtolower($instance->getCode());
        // Check if is enabled for anonymous
        if(!Mage::getSingleton('customer/session')->isLoggedIn()){
          if(Mage::getStoreConfig(self::ENABLE_PAYMENT_CHECK_ANONYMOUS_CFG_PATH)){
            $methodsAndLimits = Epoint_SwissPost_Api_Helper_Data::extractDefaultValues(self::ENABLE_PAYMENT_ANONYMOUS_LIMITS_CFG_PATH);
            foreach ($methodsAndLimits as $method_code=>$limit){
              // Was the method configured ?
              if(strtolower($method_code) == $check_method_code){
                $quoteObj = Mage::getSingleton('checkout/session')->getQuote();
                // compare diff
                if(is_object($quoteObj)){
                  $total = $quoteObj->getTotals();
                  // disable it if total is greatest than limit.
                  if($total > 0 && $total >= $limit){
                    $result = $observer->getResult();
                    $result->isAvailable = false;
                    return ;
                  }
                }
              }
            }
          }
        // is auth, but is not on admin
        }elseif(!Mage::app()->getStore()->isAdmin()){
          if(Mage::getStoreConfig(self::ENABLE_PAYMENT_ENABLE_CHECK_CREDIT_CFG_PATH)){
            $methodsList = explode(',', strtolower(Mage::getStoreConfig(self::ENABLE_PAYMENT_ANONYMOUS_LIMITS_CFG_PATH)));
            foreach ($methodsList as $method_code=>$limit){
              if(in_array($check_method_code, $methodsList)){
                $quoteObj = Mage::getSingleton('checkout/session')->getQuote();
                // was email configured
                if(is_object($quoteObj)){
                  if($quote->getCustomer()){
                    $connection = Mage::getModel('swisspost_customer/odoo')->loadCustomerConnection($quote->getCustomer());
                    $total = $quoteObj->getTotals();
                    if($connection->isConnected() && $total > 0){
                      $response = Mage::helper('swisspost_api/Customer')->checkCustomerCredit($connection->__toAccountRef(), $total);
                      // Disable it.
                      if((int)$response->getResult('check_ok') != 1){
                        $result = $observer->getResult();
                        $result->isAvailable = false;
                      }
                    }
                  }
                }
              }
            }
          }
        }
    }
}