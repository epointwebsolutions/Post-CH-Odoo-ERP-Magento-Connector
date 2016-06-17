<?php
/**
 * Extends Mage_Sales_Model_Order_Pdf_Shipment
 */
class Epoint_SwissPostSales_Model_Order_Pdf_Shipment extends Mage_Sales_Model_Order_Pdf_Shipment{
	
	 /**
     * @Override getPdf
     *  Return PDF document
     *
     * @param  array $shipments
     * @return Zend_Pdf
     */
    public function getPdf($shipments = array())
    {
    	if(Mage::getStoreConfig(Epoint_SwissPostSales_Helper_Order::ENABLE_USE_API_PDF_INVOICE_CONFIG_PATH)){
    		$mainPDfDoc = new Zend_Pdf();
     		 // For each pdf file
	      	foreach ($shipments as $shipment) {
	        	if ($shipment->getStoreId()) {
	            Mage::app()->getLocale()->emulate($shipment->getStoreId());
	            Mage::app()->setCurrentStore($shipment->getStoreId());
	        }
	        $pdf = Mage::helper('swisspostsales/Shipment')->getPdf($shipment);
	        if($pdf){
	          foreach($pdf->pages as $page){
	            $clonedPage = clone $page;
	            $mainPDfDoc->pages[] = $clonedPage;
	          }
	        }
	      }
	      return $mainPDfDoc;
    	}else{
    		return parent::getPdf($shipments);
    	}
    }
}