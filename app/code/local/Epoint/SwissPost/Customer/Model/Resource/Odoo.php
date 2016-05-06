<?php
/**
 * Implement 
 *
 */
class Epoint_SwissPost_Customer_Model_Resource_Odoo extends Mage_Core_Model_Resource_Db_Abstract{
    protected function _construct(){
       $this->_init('swisspost_customer/odoo', 'connection_id');
    }
    /**
     * loader by field
     *
     * @param Epoint_SwissPost_Customer_Model_Odoo $Object
     * @param unknown_type $fieldvalue
     * @return unknown
     */
    public function loadByField(Epoint_SwissPost_Customer_Model_Odoo $Object, $fieldname, $fieldvalue)
    {
      $adapter = $this->_getReadAdapter();
	  $bind    = array('fieldname' => $fieldvalue);
	  $select  = $adapter->select()
	      ->from('epoint_swisspost_customer_odoo', 'connection_id')
	      ->where($fieldname.' = :fieldname');
	  $modelId = $adapter->fetchOne($select, $bind);
	  if ($modelId) {
	    $this->load($Object, $modelId );
	  } else {
	    $Object->setData(array());
	  }
	  return $Object;
	}
}