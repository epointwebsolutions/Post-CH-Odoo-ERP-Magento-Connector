<?php
$installer = $this;
$installer->startSetup();
if ($installer->tableExists($installer->getTable('sales/order'))) {
  $connection = $this->getConnection();
  $setup = Mage::getModel('sales/entity_setup', 'core_setup');
  /*$installer->run("
    
    ALTER TABLE {$installer->getTable('sales/order')}
    ADD `odoo_id` integer(11) default 0,
    ADD `odoo_processed_timestamp` integer(11) default 0;
    
    ALTER TABLE {$this->getTable('sales/order')}
    ADD INDEX (`odoo_id`);
    
    ALTER TABLE {$this->getTable('sales/order')}
    ADD INDEX (`odoo_processed_timestamp`);
  ");*/
  $connection->addColumn(
    $this->getTable('sales/order'),
    'odoo_id',
    "integer(11) default 0"
  );
  $connection->addKey($this->getTable('sales/order'), 'odoo_id', 'odoo_id');
  
  $connection->addColumn(
      $this->getTable('sales/order'),
      'odoo_processed_timestamp',
      "integer(11) default 0"
  );
  $connection->addKey($this->getTable('sales/order'), 'odoo_processed_timestamp', 'odoo_processed_timestamp');
  
  $setup->addAttribute('order', 'odoo_id', array(
    'label' => 'Odoo id',
    'type' => 'int',
    'input' => 'text',
    'visible' => false,
    'required' => false,
    'visible_on_front'  => false,
    'default' => '0',
    )
  );
  $setup->addAttribute('order', 'odoo_processed_timestamp', array(
    'label' => 'Odoo processed timestamp',
    'type' => 'int',
    'input' => 'text',
    'visible' => false,
    'required' => false,
    'visible_on_front'  => false,
    'default' => '0',
    )
  );
}

$installer->endSetup();