<?php

class Epoint_SwissPostDebug_Model_Observer
{
    /**
     * Add product import Button
     *
     * @param Varien_Event_Observer $observer
     */
    public function addProductImportButton($observer)
    {
        $_block = $observer->getBlock();
        $_type = $_block->getType();
        if ($_type == 'adminhtml/catalog_product_edit') {

            $_block->setChild(
                'product_import_button',
                $_block->getLayout()->createBlock('swisspostdebug/adminhtml_widget_button')
            );

            $_deleteButton = $_block->getChild('delete_button');
            /* Prepend the new button to the 'Delete' button if exists */
            if (is_object($_deleteButton)) {
                $_deleteButton->setBeforeHtml($_block->getChild('product_import_button')->toHtml());
            } else {
                /* Prepend the new button to the 'Reset' button if 'Delete' button does not exist */
                $_resetButton = $_block->getChild('reset_button');
                if (is_object($_resetButton)) {
                    $_resetButton->setBeforeHtml($_block->getChild('product_import_button')->toHtml());
                }
            }
        }

    }

    /**
     * Add connect button on sales order
     * Listen: adminhtml_widget_container_html_before
     *
     * @param Varien_Event_Observer $observer
     */
    function adminhtmlWidgetContainerHtmlBefore(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View) {
            $order = $observer->getOrder();
            // is connected	?
            if ($order && is_object($order)
                && $order->getData(
                    Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID
                )
            ) {
                $label = Mage::helper('swisspostdebug')->__(
                    'Export to SwissPost (%s)', Epoint_SwissPostSales_Helper_Data::ORDER_ATTRIBUTE_CODE_ODOO_ID
                );
            } else {
                $label = Mage::helper('swisspostdebug')->__('Export to SwissPost');
            }
            $block->addButton(
                'sendapi', array(
                    'label'   => $label,
                    'onclick' => "setLocation('{$block->getUrl('*/sales_order_sendapi/order')}')",
                    'class'   => 'go'
                )
            );
        }
    }
}