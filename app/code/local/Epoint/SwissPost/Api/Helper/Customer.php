<?php
/**
 * Customer Helper
 *
 */
class Epoint_SwissPost_Api_Helper_Customer extends Mage_Core_Helper_Abstract {
  /**
   * Accounts and Addresses

	You can create, search, read and update accounts and addresses from Odoo.
	An account corresponds to a user that has access to the webshop.
	An account can contain multiple addresses of different type (invoice, shipping, default).
	This API supports two styles of handling account and address data:
    In the simplest case, the webshop provides all the user data in every sale order. The API for accounts and addresses is then unnecessary, and the webshop can store information on their side if they so desire. Odoo will create single-use accounts and addresses that will be used only for the current order.
    In a more complex case, the webshop can use this interface to store accounts and addresses in Odoo. These accounts and addresses can be created, modified, used for sale orders and queried for availability of customer credit through the API.
	create_update_account
	Goal
	Create or update an account in Odoo.
	If an existing account with the supplied account_ref is found, it will be updated with the fields supplied. Otherwise, a new account will be created.
   * POST /ecommerce_api_v2/create_update_account
   * @see http://post-ecommerce-xml.braintec-group.com/doc/Swisspost-WS-v2-doc/account.html#create-update-account
   * @param object customer
   * @return result objectt
   */
  public function createUpdateAccount(Mage_Customer_Model_Customer $customer){
    $account = (object)New stdClass();
    $event_data = array(
                    			'customer'=>$customer,
                        		'account'=>$account
                        	);
    // Share action
    Mage::dispatchEvent('swisspost_api_before_create_update_account', $event_data);
    /**
    
		    Name 	Type 	Comment 	Required 	Extra Info
		account_ref 	string 	A unique ID chosen by the webshop 	TRUE 	size=35
		account_title 	string 	The title used to address the customer. See Titles for details. 	FALSE 	size=64
		account_firstname 	string 	The first name of the customer 	FALSE 	size=35
		account_lastname 	string 	The last name of the customer 	TRUE 	size=35
		account_gender 	string 	One of male, female or other 	FALSE 	 
		account_address_type 	string 	
		
		Can be one of:
		
		    default
		    invoice
		    shipping
		    mypost24
		    pickpost
		
			TRUE 	size=64
		account_company 	string 	The name of the company of the customer 	FALSE 	size=35
		account_function 	string 	The function the person has in their company, or the Department 	FALSE 	size=35
		account_street 	string 	The street name 	FALSE 	size=35
		account_street_no 	string 	The street number 	FALSE 	size=5
		account_street2 	string 	The second line of the street address (aka address suffix) 	FALSE 	size=35
		account_po_box 	string 	The PO BOX number 	FALSE 	size=25
		account_mypost 	string 	My Post 24 account 	FALSE 	size=64
		account_zip 	string 	The ZIP code. See ZIP Codes for details. 	FALSE 	size=64
		account_city 	string 	The town 	FALSE 	size=64
		account_country 	string 	The country 	FALSE 	size=2
		account_email 	string 	The email address, following the RegEx “([^@]+@[^.]+..+)?” 	FALSE 	size=40
		account_phone 	string 	The main phone number 	FALSE 	size=64
		account_mobile 	string 	The mobile number 	FALSE 	size=64
		account_fax 	string 	The Fax number 	FALSE 	size=64
		account_comment 	text 	A free, private comment on the account 	FALSE 	unlimited size
		account_website 	string 	The Website 	FALSE 	size=64
		account_lang 	string 	
		
		The preferred language. Can be one of:
		
		    EN
		    DE
		    FR
		    IT
		
		All country codes will be also accepted lowercase.
			FALSE 	size=6
		account_maintag 	string 	
		
		One of the following:
		
		    internal
		    b2b
		    b2c
		
			TRUE 	size=64
		active 	boolean 	This allows to disable the record. 	FALSE 	default=TRUE
	*/
    $helper = Mage::helper('swisspost_api/Api');
    $result = $helper->call('create_update_account', array('account'=>(array)$account));
    // Share action
    Mage::dispatchEvent('swisspost_api_after_create_update_account',
                    		array(
                    			'customer'=>$customer,
                        		'account'=>$account,
                        		'result'=>$result
                        	)
                        );
    return $result;
  }
   /**
   * Accounts and Addresses

	You can create, search, read and update accounts and addresses from Odoo.
	An account corresponds to a user that has access to the webshop.
	An account can contain multiple addresses of different type (invoice, shipping, default).
	This API supports two styles of handling account and address data:
    In the simplest case, the webshop provides all the user data in every sale order. The API for accounts and addresses is then unnecessary, and the webshop can store information on their side if they so desire. Odoo will create single-use accounts and addresses that will be used only for the current order.
    In a more complex case, the webshop can use this interface to store accounts and addresses in Odoo. These accounts and addresses can be created, modified, used for sale orders and queried for availability of customer credit through the API.
	create_update_account
	Goal
	Create or update an account in Odoo.
	If an existing account with the supplied account_ref is found, it will be updated with the fields supplied. Otherwise, a new account will be created.
   * POST /ecommerce_api_v2/create_update_account
   * @see http://post-ecommerce-xml.braintec-group.com/doc/Swisspost-WS-v2-doc/account.html#create-update-account
   * @param object customer
   * @return result objectt
   */
  public function createUpdateAnonymousAccount(Mage_Core_Model_Abstract $address, Mage_Core_Model_Abstract $order){
    $account = (object)New stdClass();
    $event_data = array(
                    			'order'=>$order,
                    			'address'=>$address,
                        		'account'=>$account
                        	);
    // Share action
    Mage::dispatchEvent('swisspost_api_before_create_update_anonymous_account', $event_data);
    /**
    
		    Name 	Type 	Comment 	Required 	Extra Info
		account_ref 	string 	A unique ID chosen by the webshop 	TRUE 	size=35
		account_title 	string 	The title used to address the customer. See Titles for details. 	FALSE 	size=64
		account_firstname 	string 	The first name of the customer 	FALSE 	size=35
		account_lastname 	string 	The last name of the customer 	TRUE 	size=35
		account_gender 	string 	One of male, female or other 	FALSE 	 
		account_address_type 	string 	
		
		Can be one of:
		
		    default
		    invoice
		    shipping
		    mypost24
		    pickpost
		
			TRUE 	size=64
		account_company 	string 	The name of the company of the customer 	FALSE 	size=35
		account_function 	string 	The function the person has in their company, or the Department 	FALSE 	size=35
		account_street 	string 	The street name 	FALSE 	size=35
		account_street_no 	string 	The street number 	FALSE 	size=5
		account_street2 	string 	The second line of the street address (aka address suffix) 	FALSE 	size=35
		account_po_box 	string 	The PO BOX number 	FALSE 	size=25
		account_mypost 	string 	My Post 24 account 	FALSE 	size=64
		account_zip 	string 	The ZIP code. See ZIP Codes for details. 	FALSE 	size=64
		account_city 	string 	The town 	FALSE 	size=64
		account_country 	string 	The country 	FALSE 	size=2
		account_email 	string 	The email address, following the RegEx “([^@]+@[^.]+..+)?” 	FALSE 	size=40
		account_phone 	string 	The main phone number 	FALSE 	size=64
		account_mobile 	string 	The mobile number 	FALSE 	size=64
		account_fax 	string 	The Fax number 	FALSE 	size=64
		account_comment 	text 	A free, private comment on the account 	FALSE 	unlimited size
		account_website 	string 	The Website 	FALSE 	size=64
		account_lang 	string 	
		
		The preferred language. Can be one of:
		
		    EN
		    DE
		    FR
		    IT
		
		All country codes will be also accepted lowercase.
			FALSE 	size=6
		account_maintag 	string 	
		
		One of the following:
		
		    internal
		    b2b
		    b2c
		
			TRUE 	size=64
		active 	boolean 	This allows to disable the record. 	FALSE 	default=TRUE
	*/
    $helper = Mage::helper('swisspost_api/Api');
    $result = $helper->call('create_update_account', array('account'=>(array)$account));
    // Share action
    Mage::dispatchEvent('swisspost_api_after_create_update_anonymous_account',
                    		array(
                    			'order'=>$order,
                    			'address'=>$address,
                        		'account'=>$account,
                        		'result'=>$result
                        	)
                        );
    return $result;
  }
  /**
   * Accounts and Addresses
   * Returns information about all accounts.
   * POST /ecommerce_api_v2/search_read_account
   * @see http://post-ecommerce-xml.braintec-group.com/doc/Swisspost-WS-v2-doc/account.html#search-read-account
   * @param object customer
   * @return result objectt
   */
  public function searchReadAccount($options = array()){
  	 // Required fields.
    // Search filters. See Search filters chapter. Example: ['name = ABC'] 	
    if(!isset($options['filters'])){
      $options['filters'] = array();
    }
    // f provided, the response returns only the asked fields. Example: ['account_last_name', 'account_first_name']. Otherwise, if this is an empty list, it will return all fields detailed below
    if(!isset($options['fields'])){
      $options['fields'] = array();
    }
    $helper = Mage::helper('swisspost_api/Api');
    return $helper->call('search_read_account', $options);
  }
}