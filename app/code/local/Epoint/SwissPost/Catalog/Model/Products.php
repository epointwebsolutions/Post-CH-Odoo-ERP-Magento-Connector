<?php

/**
 * Data Helper
 *
 */
class Epoint_SwissPost_Catalog_Model_Products extends Mage_Core_Model_Abstract
{
    const PRODUCT_DEBUG_ATTIBUTE_CODE = 'epoint_swisspost_json_content';
    const ODOO_PRODUCT_LANGUAGE_ATTRIBUTE_CODE = 'languages';


    /**
     * Import products
     *
     * @param array $items
     */
    public function import($items = array())
    {
        foreach ($items as $_item) {
            $odoo_id = $_item['odoo_id'];

            $current_product = Mage::getModel('catalog/product')->loadByAttribute('odoo_id', $odoo_id);

            //check if product not exist
            //will add new product
            if (!$current_product || $current_product->getId()) {
                $product = Mage::getModel('catalog/product');
                $logMsg = "Product added: odoo_id = $odoo_id";
            } //will update the current product
            else {
                $product = $current_product;
                $logMsg = "Product updated: odoo_id = $odoo_id";
            }

            $product = $this->setGeneralAttributes($product, $_item);
            $product = $this->setRelatedProducts($product, $_item);
            $product = $this->setCategories($product, $_item);
            $imageAttached = 0;
            if ($product->getId()
                && Mage::getStoreConfig(
                    Epoint_SwissPost_Catalog_Helper_Data::XML_CONFIG_ENABLE_IMPORT_IMAGES
                )
            ) {
                $product = $this->attachImage($product, $_item);
                $imageAttached = 1;
            }
            try {
                $product->save();
                // save attributes defined by store
                $this->saveStoresAttributes($product, $_item);
                // If product was created, it need after that to attach images
                if (!$imageAttached && $product->getId()
                    && Mage::getStoreConfig(
                        Epoint_SwissPost_Catalog_Helper_Data::XML_CONFIG_ENABLE_IMPORT_IMAGES
                    )
                ) {
                    $product = $this->attachImage($product, $_item);
                    if ($product->getData('attached_images')) {
                        $product->save();
                    }
                }
                Mage::helper('swisspost_api')->log($logMsg);
            } catch (Exception $e) {
                Mage::helper('swisspost_api')->log("ERROR: while processing product with odoo_id=$odoo_id");
                Mage::helper('swisspost_api')->logException($e);
            }
        }
    }

    /**
     * Update inventory
     *
     * @param array $items
     */
    public function updateInventory($items = array())
    {
        foreach ($items as $_item) {
            $product_code = $_item['product_code'];
            $qty_on_sale = $_item['qty_on_sale'];
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $product_code);
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
            try {
                if (!$stockItem->getId()) {
                    $stockItem->setData('product_id', $product->getId());
                    $stockItem->setData('stock_id', 1);
                }
                if ($stockItem->getId() > 0) {
                    $stockItem->setManageStock(1);
                    $stockItem->setQty($qty_on_sale);
                    $stockItem->setIsInStock((int)($qty_on_sale > 0));
                }
                $stockItem->save();
                $product->save();
            } catch (Exception $e) {
                Mage::helper('swisspost_api')->log(
                    "ERROR: while processing product inventory with product_code=$product_code"
                );
                Mage::helper('swisspost_api')->logException($e);
            }
        }
    }

    /**
     * Set general attributes
     *
     * @param $product
     * @param $_item
     *
     * @return mixed
     */
    public function setGeneralAttributes($product, $_item)
    {

        $product->setTypeId('simple');
        $product->setAttributeSetId(4);
        // Get class id from config
        $product->setTaxClassId(Mage::helper('swisspost_catalog')->getTaxClassId($_item));
        $this->setStatusAndVisibility($product, $_item);
        // Set websites
        static $websitesIds;
        if (!isset($websitesIds)) {
            foreach (Mage::app()->getWebsites() as $website) {
                $websitesIds[] = $website->getId();
            }
        }
        $product->setWebsiteIds($websitesIds);
        // Add dynamic attribute mode.
        $dynamic_product_values = Mage::helper('swisspost_catalog')->__fromDynamicAttributes($_item);
        foreach ($dynamic_product_values as $attribute_code => $info) {
            $product->setData($attribute_code, $info['value']);
        }
        // Load mapping
        $mapping = Mage::helper('swisspost_api')->getMapping('catalog');
        // Apply mapping, from magento attribute code,
        $product_values = Mage::helper('swisspost_api')->__fromSwissPost($_item, $mapping);
        // Attach default values
        $default_values = Mage::helper('swisspost_api')->extractDefaultValues(
            Epoint_SwissPost_Catalog_Helper_Data::XML_CONFIG_PATH_DEFAULT_VALUES
        );
        foreach ($default_values as $key => $value) {
            $product_values[$key] = $value;
        }
        foreach ($product_values as $attribute_code => $value) {
            $product->setData($attribute_code, $value);
        }
        // Attach debug code
        $product->setData(self::PRODUCT_DEBUG_ATTIBUTE_CODE, print_r($_item, 1) . "\n" . json_encode($_item));

        return $product;
    }

    /**
     * Save attribute value, per website scope
     *
     * @param       $product
     * @param       $values
     * @param       $store_code
     * @param array $dynamic_values
     */
    public function saveStoreAttributes($product, $values, $store_code, $dynamic_values = array())
    {
        static $stores;
        if (!isset($stores)) {
            $stores = Mage::helper('swisspost_api')->getStores();
        }
        $store_id = 0;
        if (!isset($stores[$store_code])) {
            return;
        }
        $store_id = $stores[$store_code]->getId();
        // Load mapping
        $mapping = Mage::helper('swisspost_api')->getMapping('catalog');
        // Apply mapping, from magento attribute code,
        $product_values = Mage::helper('swisspost_api')->__fromSwissPost($values, $mapping, $checkIfSet = true);
        foreach ($dynamic_values as $attribute_code => $value) {
        	$attribute_code = trim($attribute_code);
        	if($attribute_code && Epoint_SwissPost_Api_Helper_Data::attributeEavExists($attribute_code)){
        		$product_values[$attribute_code] = $value;	
        	}else{
        		Mage::helper('swisspost_api')->log('Invalid product attribute code configured:'.$attribute_code, Zend_Log::ERR);
        	}
            
        }
        $updates = array();
        foreach ($product_values as $attributeCode => $value) {
	        if ($product->getData($attributeCode) != $value) {
	            $updates[$attributeCode] = $value;
	        }
        }
        // Exists diffs
        if ($updates) {
            Mage::getSingleton('catalog/product_action')
                ->updateAttributes(array($product->getId()), $updates, $store_id);
        }
    }

    /**
     * Save attribute value, per website scope
     *
     * @param $product
     * @param $item
     */
    public function saveStoresAttributes($product, $item)
    {
        if (!isset($item[self::ODOO_PRODUCT_LANGUAGE_ATTRIBUTE_CODE])) {
            return;
        } elseif (!is_array($item[self::ODOO_PRODUCT_LANGUAGE_ATTRIBUTE_CODE])) {
            return;
        }
        static $mapping;
        if (!isset($mapping)) {
            $mapping = Mage::helper('swisspost_api')->textToArray(
                Mage::getStoreConfig(Epoint_SwissPost_Catalog_Helper_Data::XML_CONFIG_PATH_STORE_ATTRIBUTE_MAPPING)
            );
        }

        if (!$mapping) {
            return;
        }
        static $stores;
        if (!isset($stores)) {
            $stores = Mage::helper('swisspost_api')->getStores();
        }
        foreach ($mapping as $magento_store_code => $odoo_language_code) {
            if (!isset($stores[$magento_store_code])) {
                unset($mapping[$magento_store_code]);
            }
        }
        if (!$mapping) {
            return;
        }
        $values = $item[self::ODOO_PRODUCT_LANGUAGE_ATTRIBUTE_CODE];
        foreach ($mapping as $magento_store_code => $odoo_language_code) {
            $dynamic_attributes = Mage::helper('swisspost_catalog')->__fromDynamicAttributes($item);
            $dynamic_values = array();
            foreach ($dynamic_attributes as $mage_attribute_code => $info) {
                if (isset($info['languages'])) {
                    $dynamic_values[$mage_attribute_code] = $info['languages'][$odoo_language_code];
                }
            }
            $this->saveStoreAttributes($product, $values[$odoo_language_code], $magento_store_code, $dynamic_values);
        }
    }

    /**
     * Set related products
     *
     * @param $product
     * @param $_item
     *
     * @return mixed
     */
    public function setRelatedProducts($product, $_item)
    {
        $linkData = false;
        $alternative_products = $_item['alternative_products'];
        if (!empty($alternative_products) and is_array($alternative_products)) {
            $i = 0;
            foreach ($alternative_products as $_id) {
                $i++;
                $prod = Mage::getModel('catalog/product')->loadByAttribute('odoo_id', $_id);
                if ($prod) {
                    $prod_id[$i] = $prod->getId();
                    $linkData[$prod_id[$i]] = array('position' => $i);
                }
            }

            if ($linkData) {
                $product->setRelatedLinkData($linkData);
                Mage::helper('swisspost_api')->log("Related products:".$prod_id);
            }
        }

        return $product;
    }

    /**
     * Set product categories
     *
     * @param $product
     * @param $_item
     *
     * @return mixed
     */
    public function setCategories($product, $_item)
    {
        $cat_ids = array();
        if (isset($_item['category_ids'])) {
            $category_ids = $_item['category_ids'];
        } elseif (isset($_item['category_id']) && $_item['category_id']) {
            $category_ids = array((int)$_item['category_id']);
        }
        if (!empty($category_ids)) {
            foreach ($category_ids as $_id) {
                $category = Mage::helper('swisspost_catalog/Category')->getFromStaticByOdooId($_id);
                if ($category && $category->getId()) {
                    $ids = Mage::helper('swisspost_catalog/Category')->getCategoriesId4Product($category);
                    foreach ($ids as $category_id) {
                        if (!in_array($category_id, $cat_ids)) {
                            $cat_ids[] = $category_id;
                        }
                    }
                }
            }
            if (!empty($cat_ids)) {
                $product->setCategoryIds($cat_ids);
            }
        }

        return $product;
    }

    /**
     * Get Images
     *
     * @param $product
     *
     * @return array
     */
    public function _getImages($product)
    {
        $images_array = Mage::helper('swisspost_api/Product')->getImages($product->getSku())->getValues();
        $images = array();
        foreach ($images_array as $_image) {
            $saved_image = self::_saveImage($_image, $_image['type']);
            if ($saved_image) {
                $images[] = $saved_image;
            }
        }

        return $images;
    }

    /**
     * Save image
     *
     * @param $image
     * @param $type
     *
     * @return bool|string
     */
    protected function _saveImage($image, $type)
    {
        $file_path = false;
        $put_contents = false;
        $dir_to_save = Mage::getBaseDir('tmp') . DS;
        if (!is_dir($dir_to_save)) {
            mkdir($dir_to_save);
        }

        if ($type == 'binary') {
            $file_path = $dir_to_save . $image['filename'];
            $put_contents = base64_decode($image['content']);

        } elseif ($type == 'url') {
            $image_name = basename($image['url']);
            $image_type = substr(strrchr($image_name, "."), 1); //find the image extension
            $file_name = md5($image . strtotime('now')) . '.'
                . $image_type; //give a new name, you can modify as per your requirement
            $file_path = $dir_to_save . $file_name;
            $put_contents = file_get_contents(trim($image['url']));
        }
        if (!$file_path) {
            return false;
        }
        if (!$put_contents) {
            return false;
        }
        if (!file_put_contents($file_path, $put_contents)) {
            Mage::helper('swisspost_api')->log("ERROR: file_put_contents: " . $file_path);

            return false;
        } else {
            return $file_path;
        }
    }

    /**
     * Attach image
     *
     * @param $product
     *
     * @return mixed
     */
    public static function attachImage($product)
    {
        $sku = $product->getSku();

        $files = self::_getImages($product);

        $attached_images = 0;
        // Remove unset images, add image to gallery if exists
        foreach ($files as $filepath) {
            $can_be_imported = self::_canBeImported($product, $filepath);
            switch ($can_be_imported) {
                case -11:
                    Mage::helper('swisspost_api')->log(
                        Mage::helper('core')->__('Image exists %s for sku: %s', $filepath, $sku)
                    );
                    continue 2;
                case -1:
                    Mage::helper('swisspost_api')->log(
                        Mage::helper('core')->__('Image moved %s for sku: %s', $filepath, $sku)
                    );
                    continue 2;
                case -4:
                    Mage::helper('swisspost_api')->log(
                        Mage::helper('core')->__('Image exists, found using md5 method %s for sku: %s', $filepath, $sku)
                    );
                    continue 2;
            }
            if (self::imageIsBroken($filepath)) {
                Mage::helper('swisspost_api')->log(
                    Mage::helper('core')->__('Image cannot be imported %s, is broken sku: %s', $filepath, $sku)
                );
                continue;
            }
            if (filesize($filepath)) {
                $attached_images++;
                try {
                    $product->addImageToMediaGallery($filepath, array('image'), false, false);
                    $product->setData('attached_images', $attached_images);
                    Mage::helper('swisspost_api')->log(
                        Mage::helper('core')->__('Attach image on product: %s, sku: %s', $filepath, $sku)
                    );
                } catch (Exception $e) {
                    Mage::helper('swisspost_api')->log(
                        Mage::helper('core')->__(
                            'Error on save image: %s, sku: %s, message: %s', $filepath, $sku, $e->getMessage()
                        )
                    );
                }
            } else {
                Mage::helper('swisspost_api')->log(
                    Mage::helper('core')->__(
                        'Error on save image: %s, sku: %s, message: File size is 0', $filepath, $sku
                    )
                );
            }
        }

        return $product;
    }

    /**
     * Check if image can be imported
     *
     * @param $product
     * @param $filepath
     *
     * @return int
     */
    private static function _canBeImported($product, $filepath)
    {
        static $product_items;
        $productId = $product->getId();
        $image_info = pathinfo($filepath);
        if (filesize($filepath) == 0) {
            return -4;
        }
        // static cache
        if (!isset($product_items[$productId])) {
            $mediaApi = Mage::getModel("catalog/product_attribute_media_api");
            $product_items[$productId]['items'] = $mediaApi->items($product->getId());
            foreach ($product_items[$productId]['items'] as $k => $item) {
                $info = pathinfo($item['file']);
                // build images
                $product_items[$productId]['images'][] = $info['basename'];
                $product_items[$productId]['path'][$info['basename']] = Mage::getBaseDir('media') . DS . 'catalog' . DS
                    . 'product' . $item['file'];
                $product_items[$productId]['size'][$info['basename']] = filesize(
                    $product_items[$productId]['path'][$info['basename']]
                );
                $product_items[$productId]['md5'][$info['basename']] = md5(
                    file_get_contents($product_items[$productId]['path'][$info['basename']])
                );
            }
        }
        // check if was uploaded...
        $possible_exists = self::possibleFileExists($image_info['basename'], $product_items[$productId]['images']);
        // set exist file
        $exists = $possible_exists ? $possible_exists[0] : array();

        $md5 = md5(file_get_contents($image_info['dirname'] . DIRECTORY_SEPARATOR . $image_info['basename']));
        if (in_array($md5, $product_items[$productId]['md5'])) {
            return -4;
        }
        // Downloaded exists
        if (!is_file($filepath)) {
            return -2;
        }
        if (!filesize($filepath)) {
            return -3;
        }
        // already uploaded but diff size, delete the old one, and attach it
        if ($exists) {
            if ($product_items[$productId]['size'][$exists] <> filesize($filepath)) {
                self::_moveFile($product, $filepath, $product_items[$productId]['path'][$exists]);

                return -1;
            }

            return -11;
        }

        return 1;
    }

    /**
     * Move file
     *
     * @param $product
     * @param $filepath
     * @param $mage_file_path
     */
    private static function _moveFile($product, $filepath, $mage_file_path)
    {
        try {
            $dir = dirname($mage_file_path);
            if (!is_dir($dir)) {
                mkdir($dir, 0777);
            }
            file_put_contents($mage_file_path, file_get_contents($filepath));
            Mage::helper('swisspost_api')->log(
                Mage::helper('core')->__(
                    'Move image content: %s to %s, sku: %s', $filepath, $mage_file_path, $product->getSku()
                )
            );
        } catch (Exception $e) {
            Mage::helper('swisspost_api')->log(
                Mage::helper('core')->__(
                    'Error on move content image: %s, sku: %s, message: %s', $filepath, $product->getSku(),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Check if file was uploaded in file
     *
     * @param $file
     * @param $files
     *
     * @return array
     */
    private static function possibleFileExists($file, $files)
    {
        $file_info = pathinfo($file);
        $possible_files = array();
        foreach ($files as $possible_file) {
            $possible_file_info = pathinfo($possible_file);
            if (stripos($possible_file_info['filename'], $file_info['filename']) === 0) {
                // remove what exists
                $remain = str_ireplace($file_info['filename'], '', $possible_file_info['filename']);
                // replace
                if (!$remain) {
                    $possible_files[] = $possible_file;
                }
            }
        }

        return $possible_files;
    }

    /**
     * Check if the image is broken
     *
     * @param $image_path
     *
     * @return bool
     */
    private static function imageIsBroken($image_path)
    {
        try {
            $data = getimagesize($image_path);
            // wrong width and height?
            if (!$data || !$data[0] || !$data[1]) {
                throw new Exception(Mage::helper('core')->__('Invalid image sizes'));
            }

            return false;
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * 15??webshop_states
     * State that should be used by the webshop. Can be one of:
     * 1. on_sale: product can be seen on the webshop and is sellable
     * 2. visible: product should be seen, but not added to the basket or sold
     * 3. not_visible: product should not be visible
     * 4. not_visible_conditional: product can be seen in some conditions.
     * this can be used for example for phase-out, in which case it is
     * visible until there is stock, then it disappears
     * Status*
     * Visibility*
     * Stock Availability1. Enabled / Visible in Catalog & Search
     * 2. Enabled / Visible in Catalog & Search / Out of stock
     * 3. Disabled
     * 4. n/A
     *
     * @param $product
     * @param $item
     */
    public static function setStatusAndVisibility($product, $item)
    {
        // IF is not active, disable the product.
        if ((int)$item['active'] == 0) {
            $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
            $product->setStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
        }
        // Allow backend configuration.
        if (Epoint_SwissPost_Catalog_Helper_Data::XML_CONFIG_DISABLED_PRODUCTS_BY_TYPE) {
            static $disabled;
            if (!isset($disabled)) {
                $disabled = explode(
                    ",", str_replace(
                        array(" ", "\n", "\t"), "",
                        Mage::getStoreConfig(Epoint_SwissPost_Catalog_Helper_Data::XML_CONFIG_DISABLED_PRODUCTS_BY_TYPE)
                    )
                );
            }
            // Expose a configuration, that allow to disable automatically the stock
            if ($disabled && $item['type']) {
                if (in_array($item['type'], $disabled)) {
                    $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
                    $product->setStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED);

                    return;
                }
            }
        }
        // Check webshop states.
        switch ($item['webshop_state']) {
            case 'on_sale':
                $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
                $product->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
                break;
            case 'visible':
                $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
                $product->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
                break;
            case 'not_visible':
            case 'not_visible_conditional':
                $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
                $product->setStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
                break;

            default:
                $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
                $product->setStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
                break;
        }
    }
}