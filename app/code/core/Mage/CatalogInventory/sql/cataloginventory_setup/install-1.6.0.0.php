<?php

/**
 * Maho
 *
 * @package    Mage_CatalogInventory
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/** @var Mage_Eav_Model_Entity_Setup $this */
$installer = $this;
$installer->startSetup();

/**
 * Create table 'cataloginventory_stock'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('cataloginventory/stock'))
    ->addColumn('stock_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Stock Id')
    ->addColumn('stock_name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, [
    ], 'Stock Name')
    ->setComment('Cataloginventory Stock');
$installer->getConnection()->createTable($table);

/**
 * Create table 'cataloginventory/stock_item'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('cataloginventory/stock_item'))
    ->addColumn('item_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Item Id')
    ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ], 'Product Id')
    ->addColumn('stock_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ], 'Stock Id')
    ->addColumn('qty', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', [
        'nullable'  => false,
        'default'   => '0.0000',
    ], 'Qty')
    ->addColumn('min_qty', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', [
        'nullable'  => false,
        'default'   => '0.0000',
    ], 'Min Qty')
    ->addColumn('use_config_min_qty', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '1',
    ], 'Use Config Min Qty')
    ->addColumn('is_qty_decimal', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ], 'Is Qty Decimal')
    ->addColumn('backorders', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ], 'Backorders')
    ->addColumn('use_config_backorders', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '1',
    ], 'Use Config Backorders')
    ->addColumn('min_sale_qty', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', [
        'nullable'  => false,
        'default'   => '1.0000',
    ], 'Min Sale Qty')
    ->addColumn('use_config_min_sale_qty', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '1',
    ], 'Use Config Min Sale Qty')
    ->addColumn('max_sale_qty', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', [
        'nullable'  => false,
        'default'   => '0.0000',
    ], 'Max Sale Qty')
    ->addColumn('use_config_max_sale_qty', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '1',
    ], 'Use Config Max Sale Qty')
    ->addColumn('is_in_stock', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ], 'Is In Stock')
    ->addColumn('low_stock_date', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, [
    ], 'Low Stock Date')
    ->addColumn('notify_stock_qty', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', [
    ], 'Notify Stock Qty')
    ->addColumn('use_config_notify_stock_qty', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '1',
    ], 'Use Config Notify Stock Qty')
    ->addColumn('manage_stock', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ], 'Manage Stock')
    ->addColumn('use_config_manage_stock', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '1',
    ], 'Use Config Manage Stock')
    ->addColumn('stock_status_changed_auto', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ], 'Stock Status Changed Automatically')
    ->addColumn('use_config_qty_increments', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '1',
    ], 'Use Config Qty Increments')
    ->addColumn('qty_increments', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', [
        'nullable'  => false,
        'default'   => '0.0000',
    ], 'Qty Increments')
    ->addColumn('use_config_enable_qty_inc', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '1',
    ], 'Use Config Enable Qty Increments')
    ->addColumn('enable_qty_increments', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ], 'Enable Qty Increments')
    ->addIndex(
        $installer->getIdxName('cataloginventory/stock_item', ['product_id', 'stock_id'], Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
        ['product_id', 'stock_id'],
        ['type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE],
    )
    ->addIndex(
        $installer->getIdxName('cataloginventory/stock_item', ['product_id']),
        ['product_id'],
    )
    ->addIndex(
        $installer->getIdxName('cataloginventory/stock_item', ['stock_id']),
        ['stock_id'],
    )
    ->addForeignKey(
        $installer->getFkName('cataloginventory/stock_item', 'product_id', 'catalog/product', 'entity_id'),
        'product_id',
        $installer->getTable('catalog/product'),
        'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE,
    )
    ->addForeignKey(
        $installer->getFkName(
            'cataloginventory/stock_item',
            'stock_id',
            'cataloginventory/stock',
            'stock_id',
        ),
        'stock_id',
        $installer->getTable('cataloginventory/stock'),
        'stock_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE,
    )
    ->setComment('Cataloginventory Stock Item');
$installer->getConnection()->createTable($table);

/**
 * Create table 'cataloginventory/stock_status'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('cataloginventory/stock_status'))
    ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Product Id')
    ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Website Id')
    ->addColumn('stock_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Stock Id')
    ->addColumn('qty', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', [
        'nullable'  => false,
        'default'   => '0.0000',
    ], 'Qty')
    ->addColumn('stock_status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
    ], 'Stock Status')
    ->addIndex(
        $installer->getIdxName('cataloginventory/stock_status', ['stock_id']),
        ['stock_id'],
    )
    ->addIndex(
        $installer->getIdxName('cataloginventory/stock_status', ['website_id']),
        ['website_id'],
    )
    ->addForeignKey(
        $installer->getFkName(
            'cataloginventory/stock_status',
            'stock_id',
            'cataloginventory/stock',
            'stock_id',
        ),
        'stock_id',
        $installer->getTable('cataloginventory/stock'),
        'stock_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE,
    )
    ->addForeignKey(
        $installer->getFkName(
            'cataloginventory/stock_status',
            'product_id',
            'catalog/product',
            'entity_id',
        ),
        'product_id',
        $installer->getTable('catalog/product'),
        'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE,
    )
    ->addForeignKey(
        $installer->getFkName('cataloginventory/stock_status', 'website_id', 'core/website', 'website_id'),
        'website_id',
        $installer->getTable('core/website'),
        'website_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE,
    )
    ->setComment('Cataloginventory Stock Status');
$installer->getConnection()->createTable($table);

/**
 * Create table 'cataloginventory/stock_status_indexer_idx'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('cataloginventory/stock_status_indexer_idx'))
    ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Product Id')
    ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Website Id')
    ->addColumn('stock_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Stock Id')
    ->addColumn('qty', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', [
        'nullable'  => false,
        'default'   => '0.0000',
    ], 'Qty')
    ->addColumn('stock_status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
    ], 'Stock Status')
    ->addIndex(
        $installer->getIdxName('cataloginventory/stock_status_indexer_idx', ['stock_id']),
        ['stock_id'],
    )
    ->addIndex(
        $installer->getIdxName('cataloginventory/stock_status_indexer_idx', ['website_id']),
        ['website_id'],
    )
    ->setComment('Cataloginventory Stock Status Indexer Idx');
$installer->getConnection()->createTable($table);

/**
 * Create table 'cataloginventory/stock_status_indexer_tmp'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('cataloginventory/stock_status_indexer_tmp'))
    ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Product Id')
    ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Website Id')
    ->addColumn('stock_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Stock Id')
    ->addColumn('qty', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', [
        'nullable'  => false,
        'default'   => '0.0000',
    ], 'Qty')
    ->addColumn('stock_status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
    ], 'Stock Status')
    ->addIndex(
        $installer->getIdxName('cataloginventory/stock_status_indexer_tmp', ['stock_id']),
        ['stock_id'],
    )
    ->addIndex(
        $installer->getIdxName('cataloginventory/stock_status_indexer_tmp', ['website_id']),
        ['website_id'],
    )
    ->setComment('Cataloginventory Stock Status Indexer Tmp');
$installer->getConnection()->createTable($table);

$installer->endSetup();

$installer->getConnection()->insertForce($installer->getTable('cataloginventory/stock'), [
    'stock_id'      => 1,
    'stock_name'    => 'Default',
]);
