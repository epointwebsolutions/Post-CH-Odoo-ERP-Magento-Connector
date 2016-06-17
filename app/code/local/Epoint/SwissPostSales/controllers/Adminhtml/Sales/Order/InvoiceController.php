<?php

require_once(Mage::getModuleDir('controllers', 'Mage_Adminhtml') . DS . 'Sales' . DS . 'Order' . DS . 'InvoiceController.php');

class Epoint_SwissPostSales_Adminhtml_Sales_Order_InvoiceController extends Mage_Adminhtml_Sales_Order_InvoiceController
{
	/**
	 * Override print invoice action
	 * 
	 */
    public function printAction()
    {
    	try{
        	$invoice = $this->_initInvoice();
            if ($invoice) {
                $pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf(array($invoice));
                if(is_object($pdf) && count($pdf->pages) > 0){
                	return $this->_prepareDownloadResponse('invoice'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').
                    '.pdf', $pdf->render(), 'application/pdf');	
                }
            }
	        throw new Exception($this->__('The document is not available for downloading right now.'));
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            Mage::logException($e);
        }
        $this->_redirect('*/*/view', array('invoice_id'=>$invoice->getId()));
    }
}
