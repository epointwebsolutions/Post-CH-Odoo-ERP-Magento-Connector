<?php
/**
 * Data Helper
 *
 */
class Epoint_SwissPostSales_Helper_Data extends Mage_Core_Helper_Abstract {
/**
	* Attribute code connection
	*/
	const ORDER_ATTRIBUTE_CODE_ODOO_ID = 'odoo_id';
	const ORDER_ATTRIBUTE_CODE_ODOO_TIMESTAMP = 'odoo_processed_timestamp';
	/**
	 * Check if order has been processed
	 */
	public static function hasBeenProcessed($order){
		if($order->getData(ORDER_ATTRIBUTE_CODE_ODOO_ID)){
			return TRUE;
		}
		if($order->getData(ORDER_ATTRIBUTE_CODE_ODOO_TIMESTAMP)){
			return TRUE;
		}
		return FALSE;
	}
}