<?php
/**
 * Extends Mage_Sales_Model_Order_Pdf_Invoice
 */
class Epoint_SwissPostSales_Model_Order_Pdf_Invoice extends Mage_Sales_Model_Order_Pdf_Invoice{
  
  /**
   * Override get pdf method
   *
   * @param array $invoices
   * @return ZendPdf
   */
  function getPdf($invoices = array()){
    if(Mage::getStoreConfig(Epoint_SwissPostSales_Helper_Order::ENABLE_USE_API_PDF_INVOICE_CONFIG_PATH)){
      // Build main pdf file.
      $mainPDfDoc = new Zend_Pdf();
      // For each pdf file
      foreach ($invoices as $invoice) {
        if ($invoice->getStoreId()) {
            Mage::app()->getLocale()->emulate($invoice->getStoreId());
            Mage::app()->setCurrentStore($invoice->getStoreId());
        }
        $pdf = Mage::helper('swisspostsales/Order')->getPdf($invoice);
        if($pdf){
          foreach($pdf->pages as $page){
            $clonedPage = clone $page;
            $mainPDfDoc->pages[] = $clonedPage;
          }
        }
        return $mainPDfDoc;
      }
    }  
    return parent::getPdf($invoices);
  }
}
