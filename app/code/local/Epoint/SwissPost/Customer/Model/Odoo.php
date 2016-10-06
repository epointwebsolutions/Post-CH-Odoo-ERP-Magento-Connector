<?php

/**
 * Odoo Model
 *
 */
class Epoint_SwissPost_Customer_Model_Odoo extends Mage_Core_Model_Abstract
{
    const ATTRIBUTE_CODE_ODOO_ID = 'odoo_id';
    const ADDRESS_ATTRIBUTE_CODE_ODOO_ID = 'odoo_id';

    protected function _construct()
    {
        $this->_init('swisspost_customer/odoo');
    }

    /**
     * Magic method for call loadBy
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        if (stripos($name, 'loadBy') !== false) {
            $field = strtolower(str_ireplace('loadBy', '', $name));
            if ($field == 'customerid') {
                $field = 'customer_id';
            }
            if ($field == 'odooid') {
                $field = 'odoo_id';
            }
            if ($field == 'id' || $field == 'connectionid') {
                $field = 'connection_id';
            }
            if ($field == 'mail') {
                $arguments[0] = trim(strtolower($arguments[0]));
            }

            return $this->_getResource()->loadByField($this, $field, $arguments[0]);
        }
        throw new Exception(Mage::helper('core')->__('Invalid method name: %s', $name));
    }

    /**
     * Load Customer connection with SwissPost Account
     *
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return null
     */
    public function loadCustomerConnection(Mage_Customer_Model_Customer $customer)
    {
        $connection = null;
        if ($customer->getOdooId()) {
            $connection = $this->loadByOdooId($customer->getOdooId());
        }
        if (!$connection || !$connection->getData('connection_id')) {
            if ($customer->getEmail()) {
                $connection = $this->loadByMail($customer->getEmail());
            }
        }
        if (!$connection || !$connection->getData('connection_id')) {
            if ($customer->getId()) {
                $connection = $this->loadByCustomerId($customer->getId());
            }
        }

        return $connection;
    }

    /**
     * Set connection
     *
     * @param     $mail
     * @param int $customerId
     * @param int $odoo_id
     *
     * @return mixed
     */
    public function Connect($mail, $customerId = 0, $odoo_id = 0)
    {
        try {
            // Check connection with customer.
            $connection = $this->loadByMail($mail);
            // Create if not exists.
            if (!$connection->getId()) {
                $connection->setData('odoo_id', (int)$odoo_id);
                $connection->setData('mail', strtolower(trim($mail)));
                $connection->setData('customer_id', (int)$customerId);
                $connection->save();
            }

            return $this->loadByMail($mail);
        } catch (Exception $e) {
            Mage::helper('swisspost_api')->LogException($e);
        }

        return $this->loadByMail($mail);
    }

    /**
     * Set connection
     *
     * @param     $mail
     * @param int $customerId
     * @param int $odoo_id
     *
     * @return bool
     */
    public function reConnect($mail, $customerId = 0, $odoo_id = 0)
    {
        try {
            // Check connection with customer.
            $connection = $this->loadByMail($mail);
            // Create if not exists.
            if (!$connection) {
                $connection->setData('odoo_id', (int)$odoo_id);
                $connection->setData('mail', $mail);
                $connection->setData('customer_id', (int)$customerId);
                $connection->save();
            } else {
                if ($connection->getData('mail') != $mail
                    || $connection->getData('customer_id') != $customerId
                    || $connection->getData('odoo_id') != $odoo_id
                ) {
                    $connection->setData('odoo_id', (int)$odoo_id);
                    $connection->setData('mail', $mail);
                    $connection->setData('customer_id', (int)$customerId);
                    $connection->save();
                }
            }

            return $connection;
        } catch (Exception $e) {
            Mage::helper('swisspost_api')->LogException($e);
        }

        return false;
    }

    /**
     * Set connection
     *
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return bool|mixed|null
     */
    public function ConnectCustomer(Mage_Customer_Model_Customer $customer)
    {
        try {
            // Check connection with customer.
            $connection = $this->loadCustomerConnection($customer);
            // Create if not exists.
            if (!$connection) {
                return $this->Connect(
                    $customer->getEmail(), $customer->getId(), $customer->getData(self::ATTRIBUTE_CODE_ODOO_ID)
                );
            }

            return $connection;
        } catch (Exception $e) {
            Mage::helper('swisspost_api')->LogException($e);
        }

        return false;
    }

    /**
     * Check if an address is connected with
     *
     * @param $address
     *
     * @return bool
     */
    public static function addressIsConnected($address)
    {
        if ($address->getData(self::ADDRESS_ATTRIBUTE_CODE_ODOO_ID)) {
            return true;
        }

        return false;
    }

    /**
     * Check if an address is connected with
     *
     * @return bool
     */
    public function IsConnected()
    {
        if ($this->getData(self::ATTRIBUTE_CODE_ODOO_ID)) {
            return true;
        }

        return false;
    }

    /**
     * Load connection by Address
     *
     * @param $customer
     *
     * @return mixed|null
     */
    public function loadByCustomer($customer)
    {
        $connection = $this->loadCustomerConnection($customer);
        if (!$connection->__toAccountRef()) {
            $connection = $this->Connect(
                $customer->getEmail(),
                $customer->getId(),
                $customer->getData(self::ATTRIBUTE_CODE_ODOO_ID)
            );
        }

        return $connection;
    }

    /**
     * Load connection by Address
     *
     * @param $address
     *
     * @return mixed
     */
    public function loadByAddress($address)
    {
    		
        $connection = Mage::getModel('swisspost_customer/odoo')->connect(
            $address->getEmail(),
            (int)$address->getCustomerId(),
            0
        );
        if (!$connection->__toAccountRef()) {
            if ($address->getCustomerId()) {
                $customer = Mage::getModel('customer/customer')->load($address->getCustomerId());
                $connection = $this->Connect(
                    $customer->getEmail(),
                    $customer->getId(),
                    $customer->getData(self::ATTRIBUTE_CODE_ODOO_ID)
                );
            } else {
                $connection = $this->Connect($address->getEmail(), 0, 0);
            }
        }
        if (!$connection->__toAccountRef()) {
            $connection = $this->loadByMail($address->getEmail());
        }

        return $connection;
    }

    /**
     * Load connection by order
     *
     * @param $order
     *
     * @return mixed
     */
    public function loadByOrder($order)
    {
        // Anonymous order
        if ($order->getCustomerIsGuest()) {
        	if($order->getCustomerEmail()){
        		$connection = $this->loadByMail($order->getCustomerEmail());
        		if (!$connection || !$connection->__toAccountRef()) {
        			$connection->connectFromOrder($order);
        		}
        	}else{
	            $address = $order->getBillingAddress();
    	        $connection = $this->loadByAddress($address);
        	}
        } else {
            $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
            // Check connection
            $connection = Mage::getModel('swisspost_customer/odoo')->connect(
                $customer->getEmail(),
                $customer->getId(),
                $customer->getData(self::ATTRIBUTE_CODE_ODOO_ID)
            );

            if (!$connection || !$connection->__toAccountRef()) {
                $connection->connectFromOrder($order);
            }

        }
        return $connection;
    }

    /**
     * Connect customer from order
     *
     * @param $order
     *
     * @return mixed
     */
    public function connectFromOrder($order)
    {
        // Anonymous order
        if ($order->getCustomerIsGuest()) {
            $connection = $this->connect(
                $order->getCustomerEmail(),
                0,
                0
            );

            return $connection;
        } else {
            $attributeCode = Epoint_SwissPost_Customer_Model_Odoo::ATTRIBUTE_CODE_ODOO_ID;
            $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
            // Create connection
            $connection = $this->connect(
                $customer->getEmail(),
                $customer->getId(),
                $customer->getData($attributeCode)
            );

            return $connection;

        }
    }

    /**
     * Convert connection to account ref
     *
     * @return mixed
     */
    public function __toAccountRef()
    {
        return $this->getData('connection_id');
    }
}