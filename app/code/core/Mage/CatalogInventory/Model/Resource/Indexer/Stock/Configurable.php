<?php

/**
 * Maho
 *
 * @package    Mage_CatalogInventory
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * CatalogInventory Configurable Products Stock Status Indexer Resource Model
 *
 * @package    Mage_CatalogInventory
 */
class Mage_CatalogInventory_Model_Resource_Indexer_Stock_Configurable extends Mage_CatalogInventory_Model_Resource_Indexer_Stock_Default
{
    /**
     * Reindex stock data for defined configurable product ids
     *
     * @param int|array $entityIds
     * @return $this
     */
    #[\Override]
    public function reindexEntity($entityIds)
    {
        $this->_updateIndex($entityIds);
        return $this;
    }

    /**
     * Get the select object for get stock status by product ids
     *
     * @param int|array $entityIds
     * @param bool $usePrimaryTable use primary or temporary index table
     * @return Varien_Db_Select
     */
    #[\Override]
    protected function _getStockStatusSelect($entityIds = null, $usePrimaryTable = false)
    {
        $adapter  = $this->_getWriteAdapter();
        $idxTable = $usePrimaryTable ? $this->getMainTable() : $this->getIdxTable();
        $select  = $adapter->select()
            ->from(['e' => $this->getTable('catalog/product')], ['entity_id']);
        $this->_addWebsiteJoinToSelect($select, true);
        $this->_addProductWebsiteJoinToSelect($select, 'cw.website_id', 'e.entity_id');
        $select->columns('cw.website_id')
            ->join(
                ['cis' => $this->getTable('cataloginventory/stock')],
                '',
                ['stock_id'],
            )
            ->joinLeft(
                ['cisi' => $this->getTable('cataloginventory/stock_item')],
                'cisi.stock_id = cis.stock_id AND cisi.product_id = e.entity_id',
                [],
            )
            ->joinLeft(
                ['l' => $this->getTable('catalog/product_super_link')],
                'l.parent_id = e.entity_id',
                [],
            )
            ->join(
                ['le' => $this->getTable('catalog/product')],
                'le.entity_id = l.product_id',
                [],
            )
            ->joinLeft(
                ['i' => $idxTable],
                'i.product_id = l.product_id AND cw.website_id = i.website_id AND cis.stock_id = i.stock_id',
                [],
            )
            ->columns(['qty' => new Zend_Db_Expr('0')])
            ->where('cw.website_id != 0')
            ->where('e.type_id = ?', $this->getTypeId())
            ->group(['e.entity_id', 'cw.website_id', 'cis.stock_id']);

        $psExpr = $this->_addAttributeToSelect($select, 'status', 'e.entity_id', 'cs.store_id');
        $psCond = $adapter->quoteInto($psExpr . '=?', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);

        if ($this->_isManageStock()) {
            $statusExpr = $adapter->getCheckSql(
                'cisi.use_config_manage_stock = 0 AND cisi.manage_stock = 0',
                '1',
                'cisi.is_in_stock',
            );
        } else {
            $statusExpr = $adapter->getCheckSql(
                'cisi.use_config_manage_stock = 0 AND cisi.manage_stock = 1',
                'cisi.is_in_stock',
                '1',
            );
        }

        $optExpr = $adapter->getCheckSql("{$psCond} AND le.required_options = 0", 'i.stock_status', '0');
        $stockStatusExpr = $adapter->getLeastSql(["MAX({$optExpr})", "MIN({$statusExpr})"]);

        $select->columns([
            'status' => $stockStatusExpr,
        ]);

        if (!is_null($entityIds)) {
            $select->where('e.entity_id IN(?)', $entityIds);
        }

        return $select;
    }
}
