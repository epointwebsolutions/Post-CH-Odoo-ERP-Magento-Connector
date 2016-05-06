<?php

/**
 * Data Helper
 *
 */
class Epoint_SwissPost_Customer_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Setting path for mapping fields, default values
     */
    const XML_CONFIG_PATH_DEFAULT_VALUES = 'swisspost_api/customer/default_values';
    const XML_CONFIG_PATH_DEFAULT_GROUP = 'swisspost_api/customer/default_group';
    const XML_CONFIG_PATH_GROUP_MAPPING = 'swisspost_api/customer/group_mapping';

    /**
     * Implement methods to convert magento customer to swisspost array
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param string                       $mapping
     *
     * @return mixed
     */
    public function __toSwissPost(Mage_Customer_Model_Customer $customer, $mapping = 'customer')
    {
        // Load mapping
        $mapping = Mage::helper('swisspost_api')->getMapping('customer');
        // Apply mapping
        $account_values = Mage::helper('swisspost_api')->__toSwissPost($customer, $mapping);
        // Attach default values
        $default_values = Mage::helper('swisspost_api')->extractDefaultValues(self::XML_CONFIG_PATH_DEFAULT_VALUES);
        foreach ($default_values as $key => $value) {
            $account_values[$key] = $value;
        }
        //object customer
        /**
         *
         * 2. Accounts & Addresses:
         * 2.1    "When there is ONLY one address on the account, use it also as the account address.
         *
         * When there are both "Default billing address" and "Default shipping address" USE the "Default billing address"
         */
        if (!isset($account_values['account_gender'])) {
            $account_values['account_gender'] = $customer->getGender() == 1 ? 'male'
                : ($customer->getGender() == 2 ? 'female' : 'other');
        }
        if (!isset($account_values['account_email'])) {
            $account_values['account_email'] = $customer->getEmail();
        }
        if (!isset($account_values['account_address_type'])) {
            $account_values['account_address_type'] = 'default';
        }
        if (!isset($account_values['account_website'])) {
            $account_values['account_website'] = $customer->getWebsiteId();
        }
        // Custom behavior
        if (!($account_values['account_function'])) {
            if ($customer->getData('concordat_number')) {
                $account_values['account_function'] = $customer->getData('concordat_number');
            }
        }
        if (!isset($account_values['account_maintag'])) {
            $account_values['account_maintag'] = 'b2c';
        }
        if (!isset($account_values['account_title'])) {
            $account_values['account_title'] = Mage::helper('swisspost_api')->__toTitle($customer->getPrefix());
        }
        // load address
        $address_id = $customer->getDefaultBilling();
        if ((int)$address_id) {
            $address = Mage::getModel('customer/address')->load($address_id);
            if (!($account_values['account_company'])) {
                $account_values['account_company'] = $address->getCompany();
            }
            if (!($account_values['account_street'])) {
                $account_values['account_street'] = $address->getStreet1();
            }
            //account_street_no
            if (!($account_values['account_zip'])) {
                $account_values['account_zip'] = $address->getPostcode();
            }
            if (!($account_values['account_city'])) {
                $account_values['account_city'] = $address->getCity();
            }
            if (!($account_values['account_country'])) {
                $account_values['account_country'] = $address->getCountry();
            }
            if (!($account_values['account_phone'])) {
                $account_values['account_phone'] = $address->getTelephone();
            }
            if (isset($account_values['account_zip'])) {
                $account_values['account_zip'] = Epoint_SwissPost_Api_Helper_Address::fixZipCode(
                    $account_values['account_zip'], $account_values['account_country']
                );
            }
            if (!$account_values['account_street2']) {
                //$account_values['account_street2'] = $address->getStreet2();
                $account_values['account_street2'] = $address->getDepartment();
            }
            // Custom behavior
            if (!($account_values['account_function'])) {
                if ($address->getData('concordat_number')) {
                    $account_values['account_function'] = $address->getData('concordat_number');
                }
            }
            if (!($account_values['account_mobile'])) {
                $account_values['account_mobile'] = $address->getTelephone();
            }
            if (!($account_values['account_fax'])) {
                $account_values['account_fax'] = $address->getFax();
            }
        }
        // attach group.
        $groupName = self::__toSwissPostGroup($customer->getId());
        if ($groupName) {
            $account_values['account_categories'][] = $groupName;
        }
        $language_code = self::__toSwissPostLanguage($customer->getStoreId());
        if ($language_code) {
            $account_values['account_lang'] = $language_code;
        }
        $account_values['active'] = true;
        // When deleting a customer from Magento send an update with "active" = FALSE
        if ($customer->getData('deleting')) {
            $account_values['active'] = false;
        }

        return $account_values;
    }

    /**
     * Implement methods to convert magento customer to swisspost array
     *
     * @param Mage_Core_Model_Abstract $address
     * @param Mage_Core_Model_Abstract $order
     * @param string                   $mapping
     *
     * @return mixed
     */
    public function __toSwissPostFromAddress(Mage_Core_Model_Abstract $address, Mage_Core_Model_Abstract $order,
        $mapping = 'anonymous_customer'
    ) {
        // Load mapping
        $mapping = Mage::helper('swisspost_api')->getMapping($mapping);
        // Apply mapping
        $account_values = Mage::helper('swisspost_api')->__toSwissPost($address, $mapping);
        // Attach default values
        $default_values = Mage::helper('swisspost_api')->extractDefaultValues(self::XML_CONFIG_PATH_DEFAULT_VALUES);
        foreach ($default_values as $key => $value) {
            $account_values[$key] = $value;
        }
        //object customer
        /**
         *
         * 2. Accounts & Addresses:
         * 2.1    "When there is ONLY one address on the account, use it also as the account address.
         *
         * When there are both "Default billing address" and "Default shipping address" USE the "Default billing address"
         */
        if (!($account_values['account_gender'])) {
            $account_values['account_gender'] = $address->getGender() == 1 ? 'male'
                : ($address->getGender() == 2 ? 'female' : 'other');
        }
        if (!($account_values['account_email'])) {
            $account_values['account_email'] = $address->getEmail();
        }
        if (!($account_values['account_address_type'])) {
            $account_values['account_address_type'] = 'default';
        }
        if (!($account_values['account_website'])) {
            $account_values['account_website'] = $address->getWebsiteId();
        }
        if (!($account_values['account_maintag'])) {
            $account_values['account_maintag'] = 'b2c';
        }
        if (!($account_values['account_title'])) {
            $account_values['account_title'] = Mage::helper('swisspost_api')->__toTitle($address->getPrefix());
        }
        // load address
        if (!($account_values['account_company'])) {
            $account_values['account_company'] = $address->getCompany();
        }
        if (!($account_values['account_street'])) {
            $account_values['account_street'] = $address->getStreet1();
        }
        if (!($account_values['account_street2'])) {
            //$account_values['account_street2'] = $address->getStreet2();
            $account_values['account_street2'] = $address->getDepartment();
        }
        // Custom behavior
        if (!isset($account_values['account_function'])) {
            $account_values['account_function'] = $address->getData('concordat_number');
        }
        //account_street_no
        if (!($account_values['account_zip'])) {
            $account_values['account_zip'] = $address->getPostcode();
        }
        if (!($account_values['account_city'])) {
            $account_values['account_city'] = $address->getCity();
        }
        if (!($account_values['account_country'])) {
            $account_values['account_country'] = $address->getCountry();
        }
        if (isset($account_values['account_zip'])) {
            $account_values['account_zip'] = Epoint_SwissPost_Api_Helper_Address::fixZipCode(
                $account_values['account_zip'], $account_values['account_country']
            );
        }
        if (!($account_values['account_phone'])) {
            $account_values['account_phone'] = $address->getTelephone();
        }
        if (!($account_values['account_mobile'])) {
            $account_values['account_mobile'] = $address->getTelephone();
        }
        if (!($account_values['account_fax'])) {
            $account_values['account_fax'] = $address->getFax();
        }

        // attach group
        $groupName = self::__toSwissPostGroup(0);
        if ($groupName) {
            $account_values['account_categories'][] = $groupName;
        }
        // Attach language
        $language_code = self::__toSwissPostLanguage($order->getStoreId());
        if ($language_code) {
            $account_values['account_lang'] = $language_code;
        }
        $account_values['active'] = true;

        return $account_values;
    }

    /**
     * To Siwsspost customer group
     *
     * @param $customerId
     *
     * @return mixed
     */
    public static function __toSwissPostGroup($customerId)
    {
        static $config;
        if (!isset($config)) {
            $config = Mage::helper('swisspost_api')->textToArray(
                Mage::getStoreConfig(self::XML_CONFIG_PATH_GROUP_MAPPING)
            );
        }
        //Default group name
        $groupName = Mage::getStoreConfig(self::XML_CONFIG_PATH_DEFAULT_GROUP);
        if ($customerId) {
            $customer = Mage::getModel('customer/customer')->load($customerId);
            $customerGroupId = $customer->getGroupId();
            if ($customerGroupId) {
                $group = Mage::getModel('customer/group')->load($customerGroupId);
                if ($group->getId() && isset($config[$group->getId()])) {
                    $groupName = $config[$group->getId()];
                }
            }
        }

        return $groupName;
    }

    /**
     * To Siwsspost customer group
     *
     * @param $storeId
     *
     * @return mixed
     */
    public static function __toSwissPostLanguage($storeId)
    {
        static $languages;
        if (!isset($languages[$storeId])) {
            $languages[$storeId] = Mage::getStoreConfig('general/locale/code', $storeId);
        }
        if ($languages[$storeId]) {
            $languages[$storeId] = strtoupper(substr($languages[$storeId], 0, 2));
        }

        return $languages[$storeId];
    }
}