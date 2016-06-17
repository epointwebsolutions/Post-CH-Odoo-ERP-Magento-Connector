<?php

/**
 * Data Helper
 *
 */
class Epoint_SwissPost_Api_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Setting path for service entry point
     */
    const XML_CONFIG_PATH_API_LOG_STATUS = 'swisspost_api/log/active';
    /**
     * Log file
     *
     */
    const XML_CONFIG_PATH_API_LOG_FILE = 'swisspost_api/log/file';
    /**
     * Allowed list of titles
     *
     */
    const XML_CONFIG_PATH_API_TITLES_LIST = 'swisspost_api/data/titles';

    /**
     * Log exception
     *
     * @param $e
     */
    public static function LogException($e)
    {
        // Log it
        Mage::helper('swisspost_api/MailLog')->sendException($e);
        // Let magento to manage it
        Mage::logException($e);
    }

    /**
     * Log interface
     *
     * @param      $message
     * @param null $logLevel
     */
    public static function log($message, $logLevel = null)
    {
        if ($logLevel == null) {
            $logLevel = Zend_Log::DEBUG;
        }
        if (Mage::getStoreConfig(self::XML_CONFIG_PATH_API_LOG_STATUS)) {
            if (Mage::getStoreConfig(self::XML_CONFIG_PATH_API_LOG_FILE)) {
                Mage::log($message, $logLevel, Mage::getStoreConfig(self::XML_CONFIG_PATH_API_LOG_FILE), true);
            } else {
                Mage::log($message, $logLevel);
            }
        }
    }

    /**
     * Log interface
     *
     * @param ApiResult $result
     */
    public static function logResult(ApiResult $result)
    {

        if (!Mage::getStoreConfig(self::XML_CONFIG_PATH_API_LOG_STATUS)) {
            return;
        }
        $data = array();
        if ($result->isOK()) {
            $logLevel = Zend_Log::DEBUG;
            $data['result'] = $result->getResult();
        } else {
            $logLevel = Zend_Log::ERR;
            $data['error'] = $result->getResult();
        }
        $data['debug'] = $result->getDebug();
        $message = Mage::helper('core')->__('API CALL %s', print_r($data, 1));
        self::log($message, $logLevel);
    }
    /**
     * Return array mapping
     *
     * @param $path
     *
     * @return array|mixed
     */
    public static function getMapping($path)
    {
        $xml = Mage::getConfig()
            ->loadModulesConfiguration('config.xml')
            ->getNode('default/' . $path . '/swisspost_api')
            ->asXML();
        if ($xml) {
            return json_decode(json_encode(simplexml_load_string($xml)), 1);
        }

        return array();
    }

    /**
     * Convert local object, to Swisspost array
     *
     * @param       $fromObject
     * @param array $mapping
     *
     * @return array
     */
    public static function __toSwissPost($fromObject, $mapping = array())
    {
        $data = array();
        if (method_exists($fromObject, 'getData')) {
            $data = $fromObject->getData();
        }
        $swissPostArray = array();
        foreach ($mapping as $swissPostField => $mageField) {
            if ($mageField == '--') {
                $swissPostArray[$swissPostField] = '';
            } else {
                $swissPostArray[$swissPostField] = isset($data[$mageField]) ? $data[$mageField] : null;
            }
        }

        return $swissPostArray;
    }

    /**
     * Convert Swisspost result to array
     *
     * @param            $swissPostItem
     * @param array      $mapping
     * @param bool|false $checkIfSet
     *
     * @return array
     */
    public static function __fromSwissPost($swissPostItem, $mapping = array(), $checkIfSet = false)
    {
        $data = array();
        $swissPostArray = array();
        foreach ($mapping as $mageField => $swissPostField) {
            if ($swissPostField == '--') {
                $data[$mageField] = '';
            } else {
                if ($checkIfSet) {
                    if (isset($swissPostItem[$swissPostField])) {
                        $data[$mageField] = $swissPostItem[$swissPostField];
                    }
                } else {
                    $data[$mageField] = isset($swissPostItem[$swissPostField]) ? $swissPostItem[$swissPostField] : null;
                }
            }
        }

        return $data;
    }

    /**
     * Check if an attribute exists
     *
     * @param $attCode
     *
     * @return bool
     */
    public static function attributeEavExists($attCode)
    {
    	static $processed;
    	if(!isset($processed)){
    		$processed = array();
    	}
    	if(isset($processed[$attCode])){
    		return $processed[$attCode];
    	}
    	$processed[$attCode] = false;
    	$attribute = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', $attCode);
        if ($attribute && $attribute->getId() !== null) {
            $processed[$attCode] = true;
        }
		return $processed[$attCode];	
    }

    /**
     * Extract default values from an existing config
     * Ex: company|Microsoft, lines with # will be ignored
     *
     * @param $path
     *
     * @return array
     */
    public static function extractDefaultValues($path)
    {
        return self::textToArray(Mage::getStoreConfig($path));
    }

    /**
     * Convert string to array
     * Ex: company|Microsoft, lines start with # will be ignored
     *
     * @param        $string
     * @param string $attributeSeparator
     * @param string $lineSeparator
     * @param string $ignoreLinesStartWith
     *
     * @return array
     */
    public static function textToArray($string, $attributeSeparator = '|', $lineSeparator = "\n",
        $ignoreLinesStartWith = '#'
    ) {
        $lines = explode($lineSeparator, $string);
        $values = array();
        foreach ($lines as $line) {
            if($attributeSeparator){
              @list($key, $value) = explode($attributeSeparator, trim($line), 2);
              if ($key && $key[0] != $ignoreLinesStartWith) {
                  $values[$key] = $value;
              }  
            }elseif(trim($line)){
                $values[$line] = $line;
            }
        }
        return $values;
    }

    /**
     * Check if a title can be configured
     *
     * @param $title
     *
     * @return string
     */
    public function __toTitle($title)
    {
        static $titles;
        if (!isset($titles)) {
            $titles = explode(",", strtolower(Mage::getStoreConfig(self::XML_CONFIG_PATH_API_TITLES_LIST)));
        }
        if (in_array(strtolower($title), $titles)) {
            return $title;
        }

        return '';
    }

    /**
     * Get websites
     *
     * @return array
     */
    public function getWebsites()
    {
        static $websites;
        if (!isset($websites)) {
            $websites = array();
            $_websites = Mage::app()->getWebsites(true);
            foreach ($_websites as $website) {
                $websites[] = $website;
            }
        }

        return $websites;
    }

    /**
     * Get stores with filter
     *
     * @return array
     */
    public function getStores()
    {
        static $stores;
        if (!isset($stores)) {
            $stores = array();
            foreach ($this->getWebsites() as $website) {
                $web_sitestores = $website->getStores();
                foreach ($web_sitestores as $store) {
                    $stores[$store->getCode()] = $store;
                }
            }
        }

        return $stores;
    }

    /**
     * Product loader by sku
     *
     * @param $sku
     *
     * @return false|Mage_Core_Model_Abstract
     */
    public function loadProductBySku($sku)
    {
        $product = Mage::getModel('catalog/product');
        $product->load($product->getIdBySku($sku));

        return $product;
    }

    /**
     * Debugger
     */
    public static function debug()
    {
        $trace = debug_backtrace();
        $lines = array();
        foreach ($trace as $call) {
            $lines[] = "File: " . $call['file'] . ' : line:' . $call['line'] . ': function: ' . $call['function']
                . ' :args:' . implode('|', $call['args']);
        }
        self::log(implode("\n", $lines));
    }
}