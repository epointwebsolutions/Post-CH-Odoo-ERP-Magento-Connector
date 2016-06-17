<?php
require_once(Mage::getModuleDir('controllers', 'Mage_Adminhtml') . DS . 'Sales' . DS . 'Order' . DS . 'ShipmentController.php');

class Epoint_SwissPostSales_Adminhtml_Sales_Order_ShipmentController extends Mage_Adminhtml_Sales_Order_ShipmentController
{
	/**
	 * Override
	 */
    public function printAction()
    {
    	try{
        	 /** @see Mage_Adminhtml_Sales_Order_InvoiceController */
	        if ($shipmentId = $this->getRequest()->getParam('invoice_id')) { // invoice_id o_0
	            if ($shipment = Mage::getModel('sales/order_shipment')->load($shipmentId)) {
	                $pdf = Mage::getModel('sales/order_pdf_shipment')->getPdf(array($shipment));
	                if(is_object($pdf) && count($pdf->pages) > 0 ){
	                	return $this->_prepareDownloadResponse('packingslip'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').
	                    '.pdf', $pdf->render(), 'application/pdf');	
	                }
	            }
	        }
	        throw new Exception($this->__('The document is not available for downloading right now.'));
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            Mage::logException($e);
        }
        $this->_redirect('*/*/view', array('shipment_id'=>$shipment->getId()));
    }

}
