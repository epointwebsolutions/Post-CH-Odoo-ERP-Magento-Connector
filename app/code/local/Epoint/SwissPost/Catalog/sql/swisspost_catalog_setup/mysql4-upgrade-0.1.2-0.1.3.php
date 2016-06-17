<?php
$installer = $this;
$installer->startSetup();

$installer->addAttribute(
    Mage_Catalog_Model_Category::ENTITY, 'odoo_id', array(
    'group'    => 'General Information',
    'visible'  => 1,
    'required' => 0,
    'label'    => 'Odoo Id',
)
);

$installer->addAttribute(
    Mage_Catalog_Model_Product::ENTITY, 'odoo_id', array(
    'group'    => 'Odoo Connector',
    'visible'  => 1,
    'required' => 0,
    'label'    => 'Odoo Id',
)
);

$installer->endSetup();