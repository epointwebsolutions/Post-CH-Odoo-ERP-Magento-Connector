<?php

/**
 * Data Helper
 *
 */
class Epoint_SwissPost_Customer_Helper_Address extends Mage_Core_Helper_Abstract
{

    /**
     * Setting path for mapping fields, default values
     */
    const XML_CONFIG_PATH_DEFAULT_VALUES = 'swisspost_api/address/default_values';

    /**
     * Implement methods to convert magento customer address to swisspost array
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param Mage_Core_Model_Abstract     $customerAddress
     *
     * @return mixed
     */
    public function __toSwissPost(Mage_Customer_Model_Customer $customer, Mage_Core_Model_Abstract $customerAddress)
    {
        // Load mapping
        $mapping = Mage::helper('swisspost_api')->getMapping('address');
        // Apply mapping
        $address_values = Mage::helper('swisspost_api')->__toSwissPost($customerAddress, $mapping);
        // Attach default values
        $default_values = Mage::helper('swisspost_api')->extractDefaultValues(self::XML_CONFIG_PATH_DEFAULT_VALUES);
        foreach ($default_values as $key => $value) {
            $address_values[$key] = $value;
        }
        //object customer
        /**
         *
         * 2. Accounts & Addresses:
         * 2.1    "When there is ONLY one address on the account, use it also as the account address.
         *
         * When there are both "Default billing address" and "Default shipping address" USE the "Default billing address"
         */
        if (!isset($address_values['address_firstname'])) {
            $address_values['address_firstname'] = $customerAddress->getFirstname();
        }
        if (!isset($address_values['address_lastname'])) {
            $address_values['address_lastname'] = $customerAddress->getLastname();
        }
        if (!isset($address_values['address_gender'])) {
            $address_values['address_gender'] = $customerAddress->getGender() == 1 ? 'male'
                : ($customerAddress->getGender() == 2 ? 'female' : 'other');
        }
        if (!isset($address_values['address_email'])) {
            $address_values['address_email'] = $customer->getEmail();
        }
        if (!isset($address_values['account_address_type'])) {
            $address_values['account_address_type'] = 'default';
            if ($customerAddress->getIsDefaultBilling()) {
                $address_values['account_address_type'] = 'billing';
            } else {
                if ($customerAddress->getIsDefaultShipping()) {
                    $address_values['account_address_type'] = 'shipping';
                }
            }
        }
        if (!isset($address_values['address_maintag'])) {
            $address_values['address_maintag'] = 'b2c';
        }
        if (!isset($address_values['address_title'])) {
            $addressTitle = 'MRS';
            if ($customerAddress->getPrefix()) {
                $addressTitle = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', $customerAddress->getPrefix()));
            } elseif ($customerAddress->getGender() == 1) {
                $addressTitle = 'MR';
            }
            $address_values['address_title'] = $addressTitle;
        }
        if (!isset($address_values['address_company'])) {
            $address_values['address_company'] = $customerAddress->getCompany();
        }
        if (!isset($address_values['address_street'])) {
            $address_values['address_street'] = $customerAddress->getStreet(1);
        }
        if (!($address_values['address_street2'])) {
            $address_values['address_street2'] = $customerAddress->getDepartment();
        }
        if (!($address_values['address_po_box'])) {
            $address_values['address_po_box'] = $customerAddress->getPobox();
        }
        //address_street_no
        if (!isset($address_values['address_zip'])) {
            $address_values['address_zip'] = $customerAddress->getPostcode();
        }
        if (!isset($address_values['address_city'])) {
            $address_values['address_city'] = $customerAddress->getCity();
        }
        if (!isset($address_values['address_country'])) {
            $address_values['address_country'] = $customerAddress->getCountry();
        }
        if (isset($address_values['address_zip'])) {
            $address_values['address_zip'] = Epoint_SwissPost_Api_Helper_Address::fixZipCode(
                $address_values['address_zip'], $address_values['address_country']
            );
        }
        if (!isset($address_values['address_phone'])) {
            $address_values['address_phone'] = $customerAddress->getTelephone();
        }
        if (!isset($address_values['address_mobile'])) {
            $address_values['address_mobile'] = $customerAddress->getTelephone();
        }
        if (!isset($address_values['address_fax'])) {
            $address_values['address_fax'] = $customerAddress->getFax();
        }
        $address_values['active'] = true;
        // When deleting a customer from Magento send an update with "active" = FALSE
        if ($customerAddress->getData('deleting')) {
            $address_values['active'] = false;
        }

        return $address_values;
    }

    /**
     * Check if 2 addresses are diff
     *
     * @param       $adr1
     * @param       $adr2
     * @param array $excludeKeys
     *
     * @return bool
     */
    public static function isDifferent($adr1, $adr2, array $excludeKeys = array())
    {
        if (!count($excludeKeys)) {
            $excludeKeys = array(
                'entity_id',
                'entity_type_id',
                'attribute_set_id',
                'is_active',
                'increment_id',
                'parent_id',
                'created_at',
                'updated_at',
                'customer_id',
                'customer_address_id',
                'quote_address_id',
                'region_id',
                'address_type',
                'is_default_billing',
                'is_default_shipping',
                'save_in_address_book',
                'odoo_id',
            );
        }
        $excludeKeys = array_flip($excludeKeys);
        $adr1Filtered = array_diff_key($adr1->getData(), $excludeKeys);
        $adr2Filtered = array_diff_key($adr2->getData(), $excludeKeys);
        $diff = array_diff_assoc($adr1Filtered, $adr2Filtered);

        return !empty($diff);
    }

    /**
     * Check if 2 addresses are same
     *
     * @param       $adr1
     * @param       $adr2
     * @param array $excludeKeys
     *
     * @return bool
     */
    public static function areSame($adr1, $adr2, array $excludeKeys = array())
    {
        return !self::isDifferent($adr1, $adr2);
    }
}