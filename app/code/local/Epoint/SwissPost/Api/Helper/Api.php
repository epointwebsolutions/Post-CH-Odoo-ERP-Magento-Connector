<?php

/**
 * Api Helper
 *
 */
class Epoint_SwissPost_Api_Helper_Api extends Mage_Core_Helper_Abstract
{
    /**
     * Api client
     *
     * @var Object
     */
    private $client;

    /**
     * Setting path for service entry point
     */
    const XML_CONFIG_PATH_API_SERVICE_URL = 'swisspost_api/settings/service_url';
    /**
     * RPC json version
     *
     */
    const XML_CONFIG_PATH_API_SERVICE_JSONRPC_VERSION = 'swisspost_api/settings/jsonrpc_version';
    /**
     * Shop ident
     *
     */
    const XML_CONFIG_PATH_API_SERVICE_SHOP_IDENT = 'swisspost_api/settings/shop_ident';
    /**
     * Username
     *
     */
    const XML_CONFIG_PATH_API_SERVICE_USERNAME = 'swisspost_api/settings/username';
    /**
     * Password
     *
     */
    const XML_CONFIG_PATH_API_SERVICE_PASSWORD = 'swisspost_api/settings/password';
    /**
     * DB
     *
     */
    const XML_CONFIG_PATH_API_SERVICE_DB = 'swisspost_api/settings/db';
    /**
     * Base API url version
     */
    const XML_CONFIG_PATH_API_SERVICE_BASE_URL_VERSION = 'swisspost_api/settings/base_url_version';

    /**
     * Implement constructor
     *
     */
    public function __construct()
    {
        $this->client = $this->__getClient();
    }

    /**
     * Get client
     *
     * @return Epoint_SwissPostClient
     */
    protected function __getClient()
    {
        static $client;
        if (!isset($client)) {
            $options = array(
                'url'             => Mage::getStoreConfig(self::XML_CONFIG_PATH_API_SERVICE_URL),
                'base_location'   => Mage::getStoreConfig(self::XML_CONFIG_PATH_API_SERVICE_URL),
                'jsonrpc'         => Mage::getStoreConfig(self::XML_CONFIG_PATH_API_SERVICE_JSONRPC_VERSION),
                'shop_ident'      => Mage::getStoreConfig(self::XML_CONFIG_PATH_API_SERVICE_SHOP_IDENT),
                'username'        => Mage::getStoreConfig(self::XML_CONFIG_PATH_API_SERVICE_USERNAME),
                'password'        => Mage::getStoreConfig(self::XML_CONFIG_PATH_API_SERVICE_PASSWORD),
                'db'              => Mage::getStoreConfig(self::XML_CONFIG_PATH_API_SERVICE_DB),
                'exceptionLogger' => Mage::helper('swisspost_api'),
                'Logger'          => Mage::helper('swisspost_api'),
            );
            $client = new Epoint_SwissPostClient($options);
        }

        return $client;
    }

    /**
     * Call the service
     *
     * @param       $method_url
     * @param array $data
     *
     * @return SwissPostResult
     */
    public function call($method_url, $data = array())
    {
        static $api_url_version;
        if (!isset($api_url_version)) {
            $api_url_version = 'ecommerce_api_v2';
            if (Mage::getStoreConfig(self::XML_CONFIG_PATH_API_SERVICE_BASE_URL_VERSION)) {
                $api_url_version = Mage::getStoreConfig(self::XML_CONFIG_PATH_API_SERVICE_BASE_URL_VERSION);
            }
        }
        $method_url = $api_url_version . '/' . $method_url;
        $result = $this->client->call($method_url, $data);
        // Send result to mail log
        Mage::helper('swisspost_api/MailLog')->sendAPIResult($result);
        Mage::helper('swisspost_api')->logResult($result);

        return $result;
    }
}