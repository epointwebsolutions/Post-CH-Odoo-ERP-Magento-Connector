<?php
$installer = $this;
$installer->startSetup();

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$setup->addAttributeGroup('catalog_product', 'Default', 'Odoo Connector', 4);

$setup->addAttribute(
    'catalog_product', 'ean13', array(
    'group'                      => 'Odoo Connector',
    'input'                      => 'text',
    'type'                       => 'text',
    'label'                      => 'Ean13',
    'backend'                    => '',
    'visible'                    => 1,
    'required'                   => 0,
    'user_defined'               => 1,
    'searchable'                 => 0,
    'filterable'                 => 0,
    'comparable'                 => 0,
    'visible_on_front'           => 0,
    'visible_in_advanced_search' => 0,
    'is_html_allowed_on_front'   => 0,
    'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
)
);

$setup->addAttribute(
    'catalog_product', 'width', array(
    'group'                      => 'Odoo Connector',
    'input'                      => 'text',
    'type'                       => 'text',
    'label'                      => 'Width',
    'backend'                    => '',
    'visible'                    => 1,
    'required'                   => 0,
    'user_defined'               => 1,
    'searchable'                 => 0,
    'filterable'                 => 0,
    'comparable'                 => 0,
    'visible_on_front'           => 0,
    'visible_in_advanced_search' => 0,
    'is_html_allowed_on_front'   => 0,
    'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
)
);

$setup->addAttribute(
    'catalog_product', 'height', array(
    'group'                      => 'Odoo Connector',
    'input'                      => 'text',
    'type'                       => 'text',
    'label'                      => 'Height',
    'backend'                    => '',
    'visible'                    => 1,
    'required'                   => 0,
    'user_defined'               => 1,
    'searchable'                 => 0,
    'filterable'                 => 0,
    'comparable'                 => 0,
    'visible_on_front'           => 0,
    'visible_in_advanced_search' => 0,
    'is_html_allowed_on_front'   => 0,
    'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
)
);

$setup->addAttribute(
    'catalog_product', 'diameter', array(
    'group'                      => 'Odoo Connector',
    'input'                      => 'text',
    'type'                       => 'text',
    'label'                      => 'Diameter',
    'backend'                    => '',
    'visible'                    => 1,
    'required'                   => 0,
    'user_defined'               => 1,
    'searchable'                 => 0,
    'filterable'                 => 0,
    'comparable'                 => 0,
    'visible_on_front'           => 0,
    'visible_in_advanced_search' => 0,
    'is_html_allowed_on_front'   => 0,
    'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
)
);

$setup->addAttribute(
    'catalog_product', 'uom_name', array(
    'group'                      => 'Odoo Connector',
    'input'                      => 'text',
    'type'                       => 'text',
    'label'                      => 'UOM Name',
    'backend'                    => '',
    'visible'                    => 1,
    'required'                   => 0,
    'user_defined'               => 1,
    'searchable'                 => 0,
    'filterable'                 => 0,
    'comparable'                 => 0,
    'visible_on_front'           => 0,
    'visible_in_advanced_search' => 0,
    'is_html_allowed_on_front'   => 0,
    'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
)
);

$setup->addAttribute(
    'catalog_product', 'volume', array(
    'group'                      => 'Odoo Connector',
    'input'                      => 'text',
    'type'                       => 'decimal',
    'label'                      => 'Volume',
    'backend'                    => '',
    'visible'                    => 1,
    'required'                   => 0,
    'user_defined'               => 1,
    'searchable'                 => 0,
    'filterable'                 => 0,
    'comparable'                 => 0,
    'visible_on_front'           => 0,
    'visible_in_advanced_search' => 0,
    'is_html_allowed_on_front'   => 0,
    'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
)
);

$setup->addAttribute(
    'catalog_product', 'weight_net', array(
    'group'                      => 'Odoo Connector',
    'input'                      => 'text',
    'type'                       => 'text',
    'label'                      => 'Weight Net',
    'backend'                    => '',
    'visible'                    => 1,
    'required'                   => 0,
    'user_defined'               => 1,
    'searchable'                 => 0,
    'filterable'                 => 0,
    'comparable'                 => 0,
    'visible_on_front'           => 0,
    'visible_in_advanced_search' => 0,
    'is_html_allowed_on_front'   => 0,
    'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
)
);

$setup->addAttribute(
    'catalog_product', 'manufacturer_website', array(
    'group'                      => 'Odoo Connector',
    'input'                      => 'text',
    'type'                       => 'text',
    'label'                      => 'Manufacturer Website',
    'backend'                    => '',
    'visible'                    => 1,
    'required'                   => 0,
    'user_defined'               => 1,
    'searchable'                 => 0,
    'filterable'                 => 0,
    'comparable'                 => 0,
    'visible_on_front'           => 0,
    'visible_in_advanced_search' => 0,
    'is_html_allowed_on_front'   => 0,
    'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
)
);

$setup->addAttribute(
    'catalog_product', 'sale_delay', array(
    'group'                      => 'Odoo Connector',
    'input'                      => 'text',
    'type'                       => 'int',
    'label'                      => 'Sale Delay',
    'backend'                    => '',
    'visible'                    => 1,
    'required'                   => 0,
    'user_defined'               => 1,
    'searchable'                 => 0,
    'filterable'                 => 0,
    'comparable'                 => 0,
    'visible_on_front'           => 0,
    'visible_in_advanced_search' => 0,
    'is_html_allowed_on_front'   => 0,
    'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
)
);


$installer->endSetup();
