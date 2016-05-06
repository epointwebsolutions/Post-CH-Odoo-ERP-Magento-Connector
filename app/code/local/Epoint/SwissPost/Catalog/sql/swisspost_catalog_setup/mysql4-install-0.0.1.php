<?php
$installer = $this;
$installer->startSetup();

$installer->addAttribute(Mage_Catalog_Model_Category::ENTITY, 'odoo_id', array(
    'input'         => 'text',
    'type'          => 'int',
    'label'         => 'Odoo Id',
    'required'      => 0,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'       => 0,
    'sort_order' => 1,
));

$installer->endSetup();