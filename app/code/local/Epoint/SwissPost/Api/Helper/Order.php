<?php
/**
 * Data Helper
 *
 */
class Epoint_SwissPost_Api_Helper_Order extends Mage_Core_Helper_Abstract {
	/**
	 * create_sale_order
	 *	
	 * Create a sale order in Odoo.
	 * This method will create a confirmed and paid order, and at present should not be used otherwise. A future version of the API might allow to create unconfirmed baskets, however orders will still be confirmed and paid my default.
	 *		The webshop has to provide its own order_ref, that will be subsequently used as a unique key in the API. The internal odoo ID is returned, but it is not necessary to store it for subsequent requests.
	 *		The account_ref field is an unique identifier for the account, chosen by the webshop. It is not required, but it is recommended that it is present because it is used as a unique key to identify the account throughout the API (for example create_update_account).
	 *		This means that if account_ref is not present, the account will only be used for the current order and will not be accessible afterwards. In any case, it must be unique.
	 *		Request
	 *	POST /ecommerce_api_v2/create_sale_order
	 *		params dictionary
	 *		Argument 	Type 	Comment 	Required
	 *		session_id 	string 	Session number 	TRUE
	 *		shop_ident 	string 	Shop identifier 	TRUE
	 * 		sale_order 	dictionary 	Details about the order to be created. See below for details. 	TRUE
	 *		
	 */
	public function createSaleOrder(Mage_Sales_Model_Order $order){
		$sale_order = new stdClass();
		$event_data = array(
                    			'order'=>$order,
                        	'sale_order'=>$sale_order
                        	);
     	
		// Share action
    	Mage::dispatchEvent('swisspost_api_before_create_order', $event_data);
    	
		/**
		 * sale_order dictionary
		The following fields constitute the sale_order dictionary.
		Name 	Type 	Comment 	Required 	Extra Info
		order_ref 	string 	Order Reference 	TRUE 	size=64
		client_order_ref 	string 	An optional code the final customer uses to refer to the order 	FALSE 	size=64
		date_order 	date 	Date of the order 	TRUE 	format=``YYYY-mm-dd``
		note 	text 	Additional printed note that is printed on documents 	FALSE 	 
		origin 	string 	Optional code of the document that generated this (for example, Amazon order number) 	FALSE 	size=64
		delivery_method 	string 	
		
		Indicates the chosen delivery method. Can be one of:
		
		    ECO: PostPac Eco
		    PRI: PostPac Prio
		    ECO__SI: PostPac Eco-SI
		    PRI__SI: PostPac Prio-SI
		    PRI__SI_AZS: PostPac Prio-SI-AZS
		    PRI__SAA: PostPac Prio-SA
		
			TRUE 	 
		payment_method 	string 	
		
		Indicates the chosen payment method. Can be one of:
		
		    INV: Invoice (In Switzerland: BVR/ESR)
		    PFE: Postfinance E-Finance
		    PFC: Postfinance Card
		    PFT: Postfinance Twint
		    MAS: Credit Card: Mastercard
		    VIS: Credit Card: Visa
		    AME: Credit Card: Amercian Express
		    PPL: Paypal
		
			TRUE 	 
		transaction_id 	string 	Indicates the payment transaction ID. It is mandatory for an electronic payment method 	FALSE 	 
		delivery_policy 	string 	
		
		Can be one of:
		
		    one (default)
		    direct
		
		Note that for standard clients, only “one” is available, while “direct” has to be negotiated with the post.
			FALSE 	 
		account 	dictionary 	The main account of the customer. It is a dictionary following the specification below 	TRUE 	 
		address_invoice 	dictionary 	The invoice address. It is a dictionary following the specification below 	TRUE 	 
		address_shipping 	dictionary 	The shipping address. It is a dictionary following the specification below 	TRUE 	 
		order_lines 	list of dictionaries 	List of order lines, see the specification below 	TRUE 	
		 */
		$helper = Mage::helper('swisspost_api/Api');
		$result = $helper->call('create_sale_order', array('sale_order'=>(array)$sale_order));
		// Share action
    	Mage::dispatchEvent('swisspost_api_after_create_order',
                    		array(
                    			'order'=>$order,
                        		'sale_order'=>$sale_order,
                        		'result'=>$result
                        	)
                        );
		return $result;                        
	}
	
  /**
   * Goal
   * Return details of invoices (account.invoice).
   * It always filter only on account.invoice which are linked to sales orders of the shop 
   * (ecommerce_api_shop.sale_order_ids).
   * POST /ecommerce_api_v2/get_payment_status
   * $otions['order_refs'], 
   * @see http://post-ecommerce-xml.braintec-group.com/doc/Swisspost-WS-v2-doc/get_product_categories.html
   * @param order_refs array
   * @return result object
   *
   */
  public function getPaymentStatus($order_refs = array()){
    $helper = Mage::helper('swisspost_api/Api');
    foreach ($order_refs as $key=>$id){
      $order_refs[$key] = " "."$id";
    }
    return $helper->call('get_payment_status', array('order_refs'=>$order_refs));
  }
}