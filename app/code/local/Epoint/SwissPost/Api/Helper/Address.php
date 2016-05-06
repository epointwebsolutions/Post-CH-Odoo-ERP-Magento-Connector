<?php

/**
 * Address Helper
 *
 * Goal
 *
 * Create or update an address in Odoo.
 *
 * If an existing address with the supplied address_ref is found, it will be updated with the fields supplied. Otherwise, a new address will be created.
 *
 */
class Epoint_SwissPost_Api_Helper_Address extends Mage_Core_Helper_Abstract
{

    /**
     * Accounts and Addresses
     * You can create, search, read and update accounts and addresses from Odoo.
     * An account corresponds to a user that has access to the webshop.
     * An account can contain multiple addresses of different type (invoice, shipping, default).
     * This API supports two styles of handling account and address data:
     * In the simplest case, the webshop provides all the user data in every sale order. The API for accounts and addresses is then unnecessary, and the webshop can store information on their side if they so desire. Odoo will create single-use accounts and addresses that will be used only for the current order.
     * In a more complex case, the webshop can use this interface to store accounts and addresses in Odoo. These accounts and addresses can be created, modified, used for sale orders and queried for availability of customer credit through the API.
     * create_update_account
     * POST /ecommerce_api_v2/create_update_account
     *
     * @see http://post-ecommerce-xml.braintec-group.com/doc/Swisspost-WS-v2-doc/account.html#create-update-account
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param Mage_Core_Model_Abstract     $customerAddress
     *
     * @return mixed
     */
    public function createUpdateAddress(Mage_Customer_Model_Customer $customer,
        Mage_Core_Model_Abstract $customerAddress
    ) {

        $address = new stdClass();
        // Share action
        Mage::dispatchEvent(
            'swisspost_api_before_create_update_address',
            array(
                'customer'         => $customer,
                'customer_address' => $customerAddress,
                'address'          => $address,
            )
        );
        /**
         * account_ref    string    The account this address belongs to    TRUE    size=35
         * address_ref    string    A code assigned to this address by the webshop    TRUE    size=35
         * address_title    string    The title used to address the customer. See Titles for details.    FALSE    size=64
         * address_firstname    string    The first name of the customer    FALSE    size=35
         * address_lastname    string    The last name of the customer    TRUE    size=35
         * address_gender    string    One of male, female or other    FALSE
         * account_address_type    string
         *
         * Can be one of:
         *
         * default
         * invoice
         * shipping
         * mypost24
         * pickpost
         *
         * TRUE    size=64
         * address_company    string    The name of the company of the customer    FALSE    size=35
         * address_function    string    The function the person has in their company, or the Department    FALSE    size=64
         * address_street    string    The street name. Required if address_po_box is not set    TRUE    size=35
         * address_street_no    string    The street number. Required if address_street is set    FALSE    size=5
         * address_street2    string    The second line of the street address (aka address suffix)    FALSE    size=35
         * address_po_box    string    The PO BOX number    FALSE    size=25
         * address_mypost    string    My Post 24 account    FALSE    size=64
         * address_zip    string    The ZIP code. See ZIP Codes for details.    FALSE    size=64
         * address_city    string    The town    FALSE    size=64
         * address_country    string    The country    FALSE    size=2
         * address_email    string    The email address, following the RegEx �([^@]+@[^.]+..+)?�    FALSE    size=40
         * address_phone    string    The main phone number    FALSE    size=64
         * address_mobile    string    The mobile number    FALSE    size=64
         * address_fax    string    The Fax number    FALSE    size=64
         * address_comment    textn    A free, private comment on the address    FALSE    unlimited size
         * address_website    string    The Website    FALSE    size=64
         * address_lang    string
         *
         * The preferred language. Can be one of:
         *
         * EN
         * DE
         * FR
         * IT
         *
         * All country codes will be also accepted lowercase.
         * FALSE    size=6
         * account_maintag    string
         *
         * One of the following:
         *
         * internal
         * b2b
         * b2c
         *
         * TRUE    size=64
         * active    boolean    This allows to disable the record.    FALSE    default=TRUE
         */
        $helper = Mage::helper('swisspost_api/Api');
        $result = $helper->call('create_update_address', array('address' => (array)$address));
        // Share action
        Mage::dispatchEvent(
            'swisspost_api_after_create_update_address',
            array(
                'customer'         => $customer,
                'customer_address' => $customerAddress,
                'address'          => $address,
                'result'           => $result,
            )
        );

        return $result;
    }

    /**
     * Accounts and Addresses
     * Returns information about all accounts.
     * POST /ecommerce_api_v2/search_read_account
     *
     * @see http://post-ecommerce-xml.braintec-group.com/doc/Swisspost-WS-v2-doc/account.html#search-read-account
     *
     * @param array $options
     *
     * @return mixed
     */
    public function searchReadAddress($options = array())
    {
        // Required fields.
        // Search filters. See Search filters chapter. Example: ['name = ABC']
        if (!isset($options['filters'])) {
            $options['filters'] = array();
        }
        // f provided, the response returns only the asked fields. Example: ['address_last_name', 'address_first_name']. Otherwise, if this is an empty list, it will return all fields detailed below
        if (!isset($options['fields'])) {
            $options['fields'] = array();
        }
        $helper = Mage::helper('swisspost_api/Api');

        return $helper->call('search_read_address', $options);
    }

    /**
     * Fix zip code to prevent failure
     *
     * @param $zipcode
     * @param $country
     *
     * @return int
     */
    public static function fixZipCode($zipcode, $country)
    {
        switch ($country) {
            case 'CH':
                $zipcode = (int)$zipcode;
                if ($zipcode == 0) {
                    $zipcode == '';
                } else {
                    if ($zipcode < 1000) {
                        $zipcode = 1000 + $zipcode;
                    } else {
                        if ($zipcode > 9999) {
                            $zipcode = 9999;
                        }
                    }
                }
                break;
            case 'DE':
                $zipcode = (int)$zipcode;
                if ($zipcode == 0) {
                    $zipcode == '';
                } else {
                    if ($zipcode < 0) {
                        $zipcode = 0;
                    } else {
                        if ($zipcode > 99999) {
                            $zipcode = 99999;
                        }
                    }
                }
                break;
        }

        return $zipcode;
    }
}