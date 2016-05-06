<?php
$installer = $this;
$installer->startSetup();

if ($installer->tableExists($installer->getTable('sales/order'))) {
	
$installer->getConnection()
    ->addColumn($installer->getTable('sales/order'),
    'odoo_id', 
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
        $installer->getTable('sales/order'),
        $installer->getIdxName('sales/order', array('odoo_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX),
        array('odoo_id'),
        Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
    );

// last timestamp odoo processed
$installer->getConnection()
    ->addColumn($installer->getTable('sales/order'),
    'odoo_processed_timestamp', 
    array(
     'type'=>Varien_Db_Ddl_Table::TYPE_INTEGER, 
     'nullable'  => false,
     'unsigned'  => true,
     'default'  => '0',
     'comment'  => 'Last timestamp odoo processed',
     )
   );
   $installer->getConnection()
    ->addIndex(
        $installer->getTable('sales/order'),
        $installer->getIdxName('sales/order', array('odoo_processed_timestamp'), Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX),
        array('odoo_id'),
        Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
    );
}   
$installer->endSetup();