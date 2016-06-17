<?php

/**
 * Data Helper
 *
 */
class Epoint_SwissPostSales_Helper_Shipment extends Mage_Core_Helper_Abstract
{
	/**
	 * Used string for look on the API result on get pdf document for shipping
	 *
	 */
	const SHIPMENT_ODOO_LOOKUP_STRING_PDF_DOC = 'delivery_order_';	
    /**
     * get pdf object for the
     *
     * @param shipment $shipment
     */
    public function getPdf(Mage_Sales_Model_Order_Shipment $shipment){
    	$pdf = null;
        $order = $shipment->getOrder();
        $sales_order_values = Mage::helper('swisspostsales/Order')->__toSwissPost($order);
        $result = Mage::helper('swisspost_api/Order')->getDeliveryDocs($sales_order_values['order_ref']);
        $documents = $result->getResult('values');
        foreach ($documents as $document){
        	if(stripos($document['filename'], self::SHIPMENT_ODOO_LOOKUP_STRING_PDF_DOC) !== FALSE){
	        	$content = base64_decode($document['content']);
	        	if($content){
	          		$pdf = Zend_Pdf::parse($content);
	          		return $pdf;
	        	}	
        	}
		  }
	    return $pdf;
    }
    /**
     * setTrackingNumber shipment 
     *
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @param array $shipment
     */
    public function setTrackingNumber(
      Mage_Sales_Model_Order $order, 
      Mage_Sales_Model_Order_Shipment $shipment, 
      $status
      )
    {
       if($status && $status['state']){
         switch ($status['state']){
           case 'done':
             try {
              foreach ($status['tracking_number'] as $number){
                // Number is not attached on this shipment, we can add it to shipment
                if(!self::existsTrackingNumber($shipment, $number)){
                   $track = Mage::getModel('sales/order_shipment_track')
                   ->setShipment($shipment)
                   ->setData('title', $order->getShippingDescription())
                   ->setData('number', $number)
                   ->setData('carrier_code', $order->getShippingMethod())
                   ->setData('order_id', $order->getId())
                   ->save();
                }
              }
             }catch(Exception $e)
             {
              	Mage::helper('swisspost_api')->LogException($e);
              	Mage::helper('swisspost_api')->log(
                      Mage::helper('core')->__('Error on add tracking number to order: %s, odoo id: %s, shipment: %s', 
                      $order->getId(), 
                      $order->getData(Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID),
                      $shipment->getIncrementId()
                     )
                 );
             }
             break;
         }
       }
    }
    /**
     * Check if exists a number attached to a shipment
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @param string $number
     */
    public static function existsTrackingNumber( Mage_Sales_Model_Order_Shipment $shipment, $number){
      // This will give me the shipment IncrementId, but not the actual tracking information.
      foreach($shipment->getAllTracks() as $tracknum)
      {
          $tracknums[] = $tracknum->getNumber();
      }
      if(in_array($number, $tracknums)){
        return true;
      }
      return false;
    }
}
