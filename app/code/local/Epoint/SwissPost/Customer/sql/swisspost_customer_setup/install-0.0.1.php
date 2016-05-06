<?php
$installer = $this;
$installer->startSetup();
$setup = Mage::getModel ( 'customer/entity_setup' , 'core_setup' );

$setup->addAttribute('customer', 'odoo_id', array(
    'input'         => 'text',
    'type'          => 'int',
    'label'         => 'Odoo Id',
    'required'      => 0,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'       => 0,
    'sort_order' => 1,
	)
);

$entityTypeId     = $setup->getEntityTypeId('customer');
$attributeSetId   = $setup->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $setup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

$setup->addAttributeToGroup(
 $entityTypeId,
 $attributeSetId,
 $attributeGroupId,
 'odoo_id',
 '999'  //sort_order
);
$used_in_forms[]="adminhtml_customer";
$attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "odoo_id");

$attribute->setData("used_in_forms", $used_in_forms)
        ->setData("is_used_for_customer_segment", true)
        ->setData("is_system", 0)
        ->setData("is_user_defined", 1)
        ->setData("is_visible", 1)
        ->setData("sort_order", 100)
        ;
$attribute->save();

$setup->addAttribute('customer_address', 'odoo_id', array(
   'input'         => 'text',
    'type'          => 'int',
    'label'         => 'Odoo Id',
    'required'      => 0,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'       => 0,
    'sort_order' => 1,
	)
);

Mage::getSingleton('eav/config')
    ->getAttribute('customer_address', 'odoo_id')
    ->setData('used_in_forms', array('adminhtml_customer_address'))
    ->save();

$installer->getConnection()
        ->addColumn($installer->getTable('sales/order_address'),'odoo_id', 
        array(
         'type'=>Varien_Db_Ddl_Table::TYPE_INTEGER, 
         'nullable'  => false,
         'unsigned'  => true,
         'default'  => '0',
         'comment'  => 'External odoo_id',
         )
       );

$installer->getConnection()
    ->addIndex(
        $installer->getTable('sales/order_address'),
        $installer->getIdxName('sales/order_address', array('odoo_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX),
        array('odoo_id'),
        Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
    );
// Create table to store  
$table = $installer->getConnection()
    ->newTable($installer->getTable('swisspost_customer/odoo'))
    ->addColumn('connection_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Connection Id')
        
    ->addColumn('mail', Varien_Db_Ddl_Table::TYPE_VARCHAR, 128, array(
        'nullable'  => false,
        'default'  => '',
        ), 'Email customer')
        
    ->addColumn('odoo_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'nullable'  => false,
        'unsigned'  => true,
        'default'  => '0',
        ), 'External odoo_id')
        
    ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'nullable'  => false,
        'unsigned'  => true,
        'default'  => '0',
        ), 'Customer id'
     );

if ($installer->tableExists($installer->getTable('swisspost_customer/odoo'))) {
    $installer->getConnection()
        ->addIndex(
            $installer->getTable('swisspost_customer/odoo'),
            $installer->getIdxName('swisspost_customer/odoo', array('mail'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
            array('mail'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        );
    $installer->getConnection()
        ->addIndex(
            $installer->getTable('swisspost_customer/odoo'),
            $installer->getIdxName('swisspost_customer/odoo', array('odoo_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX),
            array('odoo_id'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
        );
    $installer->getConnection()
        ->addIndex(
            $installer->getTable('swisspost_customer/odoo'),
            $installer->getIdxName('swisspost_customer/odoo', array('customer_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX),
            array('customer_id'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
        );
}     
if (!$installer->getConnection()->isTableExists($table->getName())) {
    $installer->getConnection()->createTable($table);
}
$installer->endSetup();