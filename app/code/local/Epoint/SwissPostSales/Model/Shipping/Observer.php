<?php

class Epoint_SwissPostSales_Model_Shipping_Observer
{
    /**
     * Receive transfer status returned from the API
     *
     * @param Varien_Event_Observer $observer
     */
    function SwissPostApiGetTransferStatus(Varien_Event_Observer $observer){
      $result = $observer->getData('result');
      $order_refs = $observer->getData('order_refs');
      if($result && $result->getValues()){
        $values = $result->getValues();
        foreach ($order_refs as $order_ref){
          foreach ($values as $status){
            if(stripos($status['order_ref'], $order_ref) !== false){   
              $order = Mage::helper('swisspostsales/Order')->__fromOrderRef($order_ref);
              if($order){
                Mage::helper('swisspostsales/Order')->processTransferStatus($order, $status);
                // set final status
                if(Mage::helper('swisspostsales/Order')->isCompleted($order)){
                  Mage::helper('swisspostsales/Order')->__toCompleted($order);
                }
              }
            }
          }
        }
      }
    }
}
