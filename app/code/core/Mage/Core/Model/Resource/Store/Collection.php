<?php

/**
 * Maho
 *
 * @package    Mage_Core
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2023 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Stores collection
 *
 * @package    Mage_Core
 *
 * @method Mage_Core_Model_Store getItemById(int $value)
 * @method Mage_Core_Model_Store[] getItems()
 */
class Mage_Core_Model_Resource_Store_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    #[\Override]
    protected function _construct()
    {
        $this->setFlag('load_default_store', false);
        $this->_init('core/store');
    }

    /**
     * Set flag for load default (admin) store
     *
     * @param bool $loadDefault
     * @return $this
     */
    public function setLoadDefault($loadDefault)
    {
        $this->setFlag('load_default_store', (bool) $loadDefault);
        return $this;
    }

    /**
     * Is load default (admin) store
     *
     * @return bool
     */
    public function getLoadDefault()
    {
        return $this->getFlag('load_default_store');
    }

    /**
     * Add disable default store filter to collection
     *
     * @return $this
     */
    public function setWithoutDefaultFilter()
    {
        $this->addFieldToFilter('main_table.store_id', ['gt' => 0]);
        return $this;
    }

    /**
     * Add filter by group id.
     * Group id can be passed as one single value or array of values.
     *
     * @param int|array $groupId
     * @return $this
     */
    public function addGroupFilter($groupId)
    {
        return $this->addFieldToFilter('main_table.group_id', ['in' => $groupId]);
    }

    /**
     * Add store id(s) filter to collection
     *
     * @param int|array $store
     * @return $this
     */
    public function addIdFilter($store)
    {
        return $this->addFieldToFilter('main_table.store_id', ['in' => $store]);
    }

    /**
     * Add filter by website to collection
     *
     * @param int|array $website
     * @return $this
     */
    public function addWebsiteFilter($website)
    {
        return $this->addFieldToFilter('main_table.website_id', ['in' => $website]);
    }

    /**
     * Add root category id filter to store collection
     *
     * @param int|array $category
     * @return $this
     */
    public function addCategoryFilter($category)
    {
        if (!is_array($category)) {
            $category = [$category];
        }
        return $this->loadByCategoryIds($category);
    }

    /**
     * Convert items array to array for select options
     *
     * @return array
     */
    #[\Override]
    public function toOptionArray()
    {
        return $this->_toOptionArray('store_id', 'name');
    }

    /**
     * Convert items array to hash for select options
     *
     * @return array
     */
    #[\Override]
    public function toOptionHash()
    {
        return $this->_toOptionHash('store_id', 'name');
    }

    #[\Override]
    public function load($printQuery = false, $logQuery = false)
    {
        if (!$this->getLoadDefault()) {
            $this->setWithoutDefaultFilter();
        }

        if (!$this->isLoaded()) {
            $this->addOrder('CASE WHEN main_table.store_id = 0 THEN 0 ELSE 1 END', Varien_Db_Select::SQL_ASC)
                ->addOrder('main_table.sort_order', Varien_Db_Select::SQL_ASC)
                ->addOrder('main_table.name', Varien_Db_Select::SQL_ASC);
        }
        return parent::load($printQuery, $logQuery);
    }

    /**
     * Add root category id filter to store collection
     *
     * @return $this
     */
    public function loadByCategoryIds(array $categories)
    {
        $this->addRootCategoryIdAttribute();
        $this->addFieldToFilter('group_table.root_category_id', ['in' => $categories]);

        return $this;
    }

    /**
     * Add store root category data to collection
     *
     * @return $this
     */
    public function addRootCategoryIdAttribute()
    {
        if (!$this->getFlag('core_store_group_table_joined')) {
            $this->getSelect()->join(
                ['group_table' => $this->getTable('core/store_group')],
                'main_table.group_id = group_table.group_id',
                ['root_category_id'],
            );
            $this->setFlag('core_store_group_table_joined', true);
        }

        return $this;
    }

    /**
     * Initializes the config cache for each store
     * @return $this
     */
    public function initConfigCache()
    {
        if (!Mage::app()->useCache('config')) {
            return $this;
        }

        $cacheId = 'store_global_config_cache';
        $globalConfigCache = Mage::app()->loadCache($cacheId);

        $data = [];
        $needsRefresh = false;

        if ($globalConfigCache !== false) {
            try {
                $data = unserialize($globalConfigCache);
            } catch (Exception $exception) {
                Mage::logException($exception);
            }
        }

        /** @var Mage_Core_Model_Store $store */
        foreach ($this as $store) {
            $code = $store->getCode();
            if (!$code) {
                continue;
            }

            if (!isset($data[$code])) {
                $data[$code] = $store->getConfigCache();
                $needsRefresh = true;
            }

            $store->setConfigCache($data[$code]);
        }

        if ($needsRefresh) {
            Mage::app()->saveCache(serialize($data), $cacheId, [
                Mage_Core_Model_Store::CACHE_TAG,
                Mage_Core_Model_Config::CACHE_TAG,
            ]);
        }

        return $this;
    }
}
