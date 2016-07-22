<?php

class Epoint_SwissPost_Customer_Model_Customer_Observer
{

    /**
     * Observer on odoo to connect magento account with
     * odoo account.
     *
     * @param Varien_Event_Observer $observer
     */
    public function beforeOrderSave(Varien_Event_Observer $observer)
    {
        $order = $observer->getOrder();
        $connection = Mage::getModel('swisspost_customer/odoo')->loadByOrder($order);
        // Anonymous order.
        if ($order->getCustomerIsGuest()) {
            if (!$connection->isConnected()) {
                $address = $order->getBillingAddress();
                Mage::helper('swisspost_api/Customer')->createUpdateAnonymousAccount($address, $order);
            }
        }
    }

    /**
     * Observer on odoo to connect magento account with
     * odoo account.
     *
     * @param Varien_Event_Observer $observer
     */
    public function afterCustomerSave(Varien_Event_Observer $observer)
    {
        // Load customer.
        $customer = $observer->getCustomer();
        // Check connection
        $connection = Mage::getModel('swisspost_customer/odoo')->loadByCustomer($customer);
        // Check connection
        if ($connection) {
            // Send API data.
            $result = Mage::helper('swisspost_api/Customer')->createUpdateAccount($customer);
        }
    }

    /**
     * Observer on odoo to connect magento account address with
     * odoo account address.
     *
     * @param Varien_Event_Observer $observer
     *  -- temporary disabled.
     */
    public function afterAddressSave(Varien_Event_Observer $observer)
    {
        $customer_address = $observer->getData('customer_address');
        $attributeCode = Epoint_SwissPost_Customer_Model_Odoo::ADDRESS_ATTRIBUTE_CODE_ODOO_ID;
        // if data changed or if customer address doesn't have set swisspost code.
        if ($customer_address->hasDataChanged()
            || !Epoint_SwissPost_Customer_Model_Odoo::addressIsConnected(
                $customer_address
            )
        ) {
            $customer = $customer_address->getCustomer();
            if ($customer->getId()) {
                $result = Mage::helper('swisspost_api/Address')->createUpdateAddress($customer, $customer_address);
                // Diff info
                if ($result->getResult('odoo_id') != $customer_address->getData($attributeCode)) {
                    $customer_address->setData($attributeCode, $result->getResult('odoo_id'));
                }
            }
        }
        $observer->setData('customer_address', $customer_address);
    }

    /**
     * Event invoke: swisspost_api_before_create_order
     * Alter data sent to API
     *
     * @param Varien_Event_Observer $observer
     */
    public function SwissPostApiBeforeCreateOrder(Varien_Event_Observer $observer)
    {
        $sale_order = $observer->getEvent()->getData('sale_order');
        $order = $observer->getEvent()->getData('order');

        // Anonymous order.
        if ($order->getCustomerIsGuest()) {
            // Attach account info...
            $address = $order->getBillingAddress();
           
            $connection = Mage::getModel('swisspost_customer/odoo')->loadByOrder($order);
            $account = Mage::helper('swisspost_customer')->__toSwissPostFromAddress(
                $address, $order, $mapping = 'order_account_anonymous'
            );
        } else {
            $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
            $connection = Mage::getModel('swisspost_customer/odoo')->loadByOrder($order);
            // Attach account info...
            $customer->setOrderStoreId($order->getStoreId());
            $account = Mage::helper('swisspost_customer')->__toSwissPost($customer, $mapping = 'order_account');
            unset($account['active']);
        }
        $sale_order->account = $account;
        // Check connection
        if (!$connection->isConnected()) {
            if ($order->getCustomerIsGuest()) {
                $address = $order->getBillingAddress();
                Mage::helper('swisspost_api/Customer')->createUpdateAnonymousAccount($address, $order);
                $connection = Mage::getModel('swisspost_customer/odoo')->loadByOrder($order);
            } else {
                $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
                Mage::helper('swisspost_api/Customer')->createUpdateAccount($customer);
                $connection = Mage::getModel('swisspost_customer/odoo')->loadByOrder($order);
            }
        }
        $sale_order->account['account_ref'] = $connection->__toAccountRef();
        // Set back sale order
        $observer->setData('sale_order', $sale_order);
    }

    /**
     * Listen event: swisspost_api_after_create_update_account
     *
     * @param Varien_Event_Observer $observer
     */
    public function SwissPostApiAfterCreateUpdateAccount(Varien_Event_Observer $observer)
    {
        // Load customer.
        $customer = $observer->getCustomer();
        $account = $observer->getAccount();
        $result = $observer->getResult();
        // Check connection
        $connection = Mage::getModel('swisspost_customer/odoo')->loadByCustomer($customer);
        $attributeCode = Epoint_SwissPost_Customer_Model_Odoo::ATTRIBUTE_CODE_ODOO_ID;
        // Get back odoo id.
        if ($result->getResult('odoo_id')) {
            // Diff info
            if ($result->getResult('odoo_id') != $customer->getData($attributeCode)) {
              $customer->setData($attributeCode, $result->getResult('odoo_id'));
              $customer->save();
            }
        }
        // Update connection.
        $connection->reConnect($customer->getEmail(), $customer->getId(), $customer->getData($attributeCode));
    }

    /**
     * Set account_ref from connection
     * Listen event:swisspost_api_after_create_update_anonymous_account
     *
     * @param Varien_Event_Observer $observer
     */
    public function SwissPostApiBeforeCreateUpdateAccount(Varien_Event_Observer $observer)
    {
        // Load customer.
        $customer = $observer->getCustomer();
        $account = $observer->getAccount();
        // Pass values
        $account_values = Mage::helper('swisspost_customer')->__toSwissPost($customer);
        // Pass values
        foreach ($account_values as $property => $value) {
            $account->{$property} = $value;
        }
        // Attach connections
        $connection = Mage::getModel('swisspost_customer/odoo')->loadByCustomer($customer);
        if ($connection) {
            $account->account_ref = $connection->__toAccountRef();
        }
        $observer->setData('account', $account);
    }

    /**
     * Set customer to inactive
     *
     * @param Varien_Event_Observer $observer
     */
    public function beforeCustomerDelete(Varien_Event_Observer $observer)
    {
        // Load customer.
        $customer = $observer->getCustomer();
        // Check connection
        $connection = Mage::getModel('swisspost_customer/odoo')->loadByCustomer($customer);
        if ($connection) {
            // Send API data.
            $customer->setData('deleting', 1);
            Mage::helper('swisspost_api/Customer')->createUpdateAccount($customer);
        }
    }

    /**
     * Listen event: swisspost_api_after_create_update_anonymous_account
     *
     * @param Varien_Event_Observer $observer
     */
    public function SwissPostApiAfterCreateUpdateAnonymousAccount(Varien_Event_Observer $observer)
    {
        // Load customer.
        $address = $observer->getAddress();
        $order = $observer->getOrder();
        $account = $observer->getAccount();
        $result = $observer->getResult();
        // Check connection
        $connection = Mage::getModel('swisspost_customer/odoo')->loadByOrder($order);
        // Get back odoo id.
        if ($result->getResult('odoo_id')) {
        	// Reconnect data.
            $connection->reConnect($account->account_email, 0, $result->getResult('odoo_id'));
        }
    }

    /**
     * Listen event swisspost_api_before_create_update_anonymous_account
     *
     * @param Varien_Event_Observer $observer
     */
    public function SwissPostApiBeforeCreateUpdateAnonymousAccount(Varien_Event_Observer $observer)
    {
        // Load data.
        $order = $observer->getOrder();
        $address = $observer->getAddress();
        $account = $observer->getAccount();
        // Pass values
        $account_values = Mage::helper('swisspost_customer')->__toSwissPostFromAddress($address, $order);
        // Pass values
        foreach ($account_values as $property => $value) {
            $account->{$property} = $value;
        }
        $connection = Mage::getModel('swisspost_customer/odoo')->loadByOrder($order);
        // Attach connections
        if ($connection) {
            $account->account_ref = $connection->__toAccountRef();
        }
        $observer->setData('account', $account);
    }
}
