<?php
include_once('Mage/Adminhtml/controllers/Catalog/ProductController.php');

class Epoint_SwissPostDebug_Adminhtml_Catalog_Product_ImportapiController
    extends Mage_Adminhtml_Catalog_ProductController
{

    /**
     * Import action
     */
    public function importAction()
    {
        $product = $this->_initProduct();
        try {
            $options['filters'] = array("product_code = " . $product->getSku());

            $result = Mage::helper('swisspost_api/Product')->getProducts($options);
            $items = $result->getValues();

            if (!empty($items)) {
                Mage::getModel('swisspost_catalog/Products')->import($items);
                $this->_getSession()->addSuccess($this->__('The product has been imported.'));
            } else {
                throw new Exception(print_r($result->getDebug(), 1));
            }

        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('Failed to import the product from API: %s.', $e->getMessage()));
            Mage::logException($e);
        }
        $this->_redirect('*/catalog_product/edit', array('id' => $product->getId()));
    }
}