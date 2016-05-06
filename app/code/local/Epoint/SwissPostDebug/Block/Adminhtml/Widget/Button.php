<?php

class Epoint_SwissPostDebug_Block_Adminhtml_Widget_Button extends Mage_Adminhtml_Block_Widget_Button
{
    /**
     * @var Mage_Catalog_Model_Product Product instance
     */
    private $_product;

    /**
     * Block construct, setting data for button, getting current product
     */
    protected function _construct()
    {
        $this->_product = Mage::registry('current_product');
        parent::_construct();
        $this->setData(array(
            'label'     => Mage::helper('swisspostdebug')->__('Import from SwissPost'),
            //'onclick'   => 'window.open(\''.Mage::getModel('core/url')->getUrl() . $this->_product->getUrlPath() .'\')',
            'onclick'   => "setLocation('{$this->getUrl('*/catalog_product_importapi/import/id/'.$this->_product->getId())}')",
            'title' => Mage::helper('swisspostdebug')->__('Import from Swiss')
        ));
    }

    /**
     * Checking product visibility
     *
     * @return bool
     */
    private function _isVisible()
    {
        return $this->_product->isVisibleInCatalog() && $this->_product->isVisibleInSiteVisibility();
    }
}