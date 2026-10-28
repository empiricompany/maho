<?php

/**
 * SPDX-FileCopyrightText: 2024-2026 Maho <https://mahocommerce.com>
 * SPDX-FileCopyrightText: 2018-2024 The OpenMage Contributors <https://openmage.org>
 * SPDX-FileCopyrightText: 2006-2020 Magento, Inc. <https://magento.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Mage_CatalogRule
 */

class Mage_CatalogRule_Model_Action_Index_Refresh
{
    protected const INDEX_INSERT_BATCH = 1000;

    /**
     * Products per pass: it keeps the rows compared in memory bounded on large catalogues
     */
    protected const INDEX_PRODUCT_CHUNK = 1000;

    /**
     * Connection instance
     *
     * @var Maho\Db\Adapter\AdapterInterface
     */
    protected $_connection;

    /**
     * Core factory instance
     *
     * @var Mage_Core_Model_Factory
     */
    protected $_factory;

    /**
     * Resource instance
     *
     * @var Mage_Core_Model_Resource_Db_Abstract
     */
    protected $_resource;

    /**
     * Application instance
     *
     * @var Mage_Core_Model_App
     */
    protected $_app;

    /**
     * Constructor with parameters
     * Array of arguments with keys
     *  - 'connection' Maho\Db\Adapter\AdapterInterface
     *  - 'factory' Mage_Core_Model_Factory
     *  - 'resource' Mage_Core_Model_Resource_Db_Abstract
     *  - 'app' Mage_Core_Model_App
     */
    public function __construct(array $args)
    {
        $this->_setConnection($args['connection']);
        $this->_setFactory($args['factory']);
        $this->_setResource($args['resource']);
        $this->_app = empty($args['app']) ? Mage::app() : $args['app'];
    }

    /**
     * Set connection
     */
    protected function _setConnection(Maho\Db\Adapter\AdapterInterface $connection)
    {
        $this->_connection = $connection;
    }

    /**
     * Set factory
     */
    protected function _setFactory(Mage_Core_Model_Factory $factory)
    {
        $this->_factory = $factory;
    }

    /**
     * Set resource
     */
    protected function _setResource(Mage_Core_Model_Resource_Db_Abstract $resource)
    {
        $this->_resource = $resource;
    }

    /**
     * Run reindex
     */
    public function execute()
    {
        $this->_app->dispatchEvent('catalogrule_before_apply', ['resource' => $this->_resource]);

        $timestamp = time();

        foreach ($this->_app->getWebsites(false) as $website) {
            if ($website->getDefaultStore()) {
                $this->_reindex($website, $timestamp);
            }
        }

        $this->_prepareGroupWebsite($timestamp);
        $this->_prepareAffectedProduct();
    }

    /**
     * Prepare temporary data
     *
     * @return Maho\Db\Select
     */
    protected function _prepareRuleProductSelect(Mage_Core_Model_Website $website)
    {
        $eavConfig = $this->_factory->getSingleton('eav/config');
        if (!$eavConfig instanceof Mage_Eav_Model_Config) {
            throw new Mage_Core_Exception('Invalid eav config model');
        }
        $priceAttribute = $eavConfig->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'price');

        $select = $this->_connection->select()
            ->from(
                ['rp' => $this->_resource->getTable('catalogrule/rule_product')],
                [],
            )
            ->joinInner(
                ['r' => $this->_resource->getTable('catalogrule/rule')],
                'r.rule_id = rp.rule_id',
                [],
            )
            ->where('rp.website_id = ?', $website->getId())
            ->order(
                ['rp.product_id', 'rp.customer_group_id', 'rp.sort_order', 'rp.rule_product_id'],
            )
            ->joinLeft(
                [
                    'pg' => $this->_resource->getTable('catalog/product_attribute_group_price'),
                ],
                'pg.entity_id = rp.product_id AND pg.customer_group_id = rp.customer_group_id'
                    . ' AND pg.website_id = rp.website_id',
                [],
            )
            ->joinLeft(
                [
                    'pgd' => $this->_resource->getTable('catalog/product_attribute_group_price'),
                ],
                'pgd.entity_id = rp.product_id AND pgd.customer_group_id = rp.customer_group_id'
                    . ' AND pgd.website_id = 0',
                [],
            );

        $storeId = $website->getDefaultStore()->getId();

        $select->joinInner(
            [
                'pd' => $this->_resource->getTable(['catalog/product', $priceAttribute->getBackendType()]),
            ],
            'pd.entity_id = rp.product_id AND pd.store_id = 0 AND pd.attribute_id = '
                    . $priceAttribute->getId(),
            [],
        )
            ->joinLeft(
                [
                    'p' => $this->_resource->getTable(['catalog/product', $priceAttribute->getBackendType()]),
                ],
                'p.entity_id = rp.product_id AND p.store_id = ' . $storeId
                    . ' AND p.attribute_id = pd.attribute_id',
                [],
            );
        // The website sells at its own row or at the default value converted, never at the raw
        // default; _reindex() already skipped a website without a rate
        $rate = (float) $this->_websitePriceRate($website);
        $converted = fn(string $column): string => (string) $this->_connection->getRoundSql("{$column} * {$rate}", 4);
        $price = $this->_connection->getCheckSql('p.value_id IS NOT NULL', 'p.value', $converted('pd.value'));
        $priceColumn = $this->_connection->getIfNullSql(
            $this->_connection->getIfNullSql(
                $this->_connection->getCheckSql(
                    'pg.is_percent = 1',
                    "({$price}) * (100 - pg.value)/100",
                    'pg.value',
                ),
                $this->_connection->getCheckSql(
                    'pgd.is_percent = 1',
                    "({$price}) * (100 - pgd.value)/100",
                    $converted('pgd.value'),
                ),
            ),
            $price,
        );

        $select->columns(
            [
                'product_id'        => 'rp.product_id',
                'customer_group_id' => 'rp.customer_group_id',
                'from_date'         => 'r.from_date',
                'to_date'           => 'r.to_date',
                'action_amount'     => 'rp.action_amount',
                'action_operator'   => 'rp.action_operator',
                'action_stop'       => 'rp.action_stop',
                'sort_order'        => 'rp.sort_order',
                'price'             => $priceColumn,
                'rule_product_id'   => 'rp.rule_product_id',
                'from_time'         => 'rp.from_time',
                'to_time'           => 'rp.to_time',
            ],
        );

        return $select;
    }

    /**
     * Remove old index data
     */
    protected function _removeOldIndexData(Mage_Core_Model_Website $website)
    {
        $this->_connection->delete(
            $this->_resource->getTable('catalogrule/rule_product_price'),
            ['website_id = ?' => $website->getId()],
        );
    }

    /**
     * Fill Index Data
     *
     * Rules chain: within one product/customer group, each rule applies to the price the previous
     * one produced, until a rule with action_stop breaks the chain. That is a running calculation
     * over ordered rows, which used to be done with MySQL user variables (SET @price := ...) and
     * so only ever worked on MySQL. It is done here instead, once per row, in the same order.
     *
     * Only the rows whose price changed are written. Deleting and reinserting a whole index that
     * did not change costs more than building it from scratch, because every row is rewritten
     * together with its index entries: a catalogue nobody touched still paid for all of them.
     */
    protected function _fillIndexData(Mage_Core_Model_Website $website, int $time): void
    {
        $table = $this->_resource->getTable('catalogrule/rule_product_price');
        $dates = $this->_getRuleDates($time);
        $productIds = $this->_getIndexedProductIds((int) $website->getId());
        if ($productIds === []) {
            return;
        }

        $chunks = array_chunk($productIds, self::INDEX_PRODUCT_CHUNK);

        $this->_connection->beginTransaction();
        try {
            $probe = $this->_fillIndexChunk($table, $website, $chunks[0], $dates, dryRun: true);
            // A row refresh covers one product, where "most of it changed" is the normal case
            // and says nothing about the rest of the index.
            if ($this->_getProduct() === null && $probe['changed'] * 2 > $probe['compared']) {
                // Most of the first group changed: the index is being rewritten, and one delete
                // followed by one pass over the website beats a delete per row.
                $where = ['website_id = ?' => (int) $website->getId()];
                if ($productIds !== []) {
                    $where['product_id IN (?)'] = $productIds;
                }
                $this->_connection->delete($table, $where);
                foreach ($chunks as $chunk) {
                    $this->_fillIndexChunk($table, $website, $chunk, $dates, replaceAll: true);
                }
            } else {
                foreach ($chunks as $chunk) {
                    $this->_fillIndexChunk($table, $website, $chunk, $dates);
                }
            }

            $this->_connection->commit();
        } catch (Throwable $e) {
            $this->_connection->rollBack();
            throw $e;
        }
    }

    /**
     * Write the index rows of one group of products
     *
     * The rows are read straight from the join and consumed in order, so the temporary table that
     * used to hold them — one INSERT ... SELECT over five joins plus a second read of the same
     * rows — is gone.
     *
     * With $replaceAll the rows are known to be gone from the table already, so nothing is
     * compared and nothing is deleted. A dry run only counts what would change.
     *
     * @param int[] $productIds
     * @param array<string, int> $dates
     * @return array{changed: int, compared: int}
     */
    protected function _fillIndexChunk(
        string $table,
        Mage_Core_Model_Website $website,
        array $productIds,
        array $dates,
        bool $replaceAll = false,
        bool $dryRun = false,
    ): array {
        $websiteId = (int) $website->getId();
        $stored = $replaceAll ? [] : $this->_loadStoredPrices($table, $websiteId, $productIds);

        $statement = $this->_connection->query(
            $this->_prepareRuleProductSelect($website)
                ->where('rp.product_id IN (?)', $productIds)
                ->order(['rp.product_id', 'rp.customer_group_id', 'rp.sort_order', 'rp.rule_product_id']),
        );

        $groupKey = null;
        $price = 0.0;
        $actionStop = 0;
        $group = [];
        $pending = [];
        $written = [];
        $replaced = [];

        while ($row = $statement->fetch()) {
            $rowGroupKey = $row['product_id'] . ':' . $row['customer_group_id'];
            if ($rowGroupKey !== $groupKey) {
                $this->_stageGroup($pending, $group, $stored, $written, $replaced);
                $group = [];
                $groupKey = $rowGroupKey;
                $price = $this->_applyRuleAction((float) $row['price'], $row);
                $actionStop = (int) $row['action_stop'];
            } else {
                if ($actionStop === 0) {
                    $price = $this->_applyRuleAction($price, $row);
                }
                $actionStop += (int) $row['action_stop'];
            }

            foreach ($dates as $date => $timestamp) {
                if ($this->_isRuleActiveAt($row, $timestamp)) {
                    $this->_collectRulePrice($group, $date, $row, $price, $websiteId);
                }
            }
        }

        $this->_stageGroup($pending, $group, $stored, $written, $replaced);

        $result = ['changed' => count($replaced), 'compared' => count($stored)];
        if ($dryRun) {
            return $result;
        }

        // A changed row cannot be inserted over itself: the unique key would reject it
        foreach (array_diff_key($stored, $written) as $key => $value) {
            $replaced[$key] = true;
        }

        if (!$replaceAll && $replaced) {
            $this->_removePrices($table, $websiteId, array_keys($replaced));
        }

        foreach (array_chunk($pending, self::INDEX_INSERT_BATCH) as $batch) {
            $this->_insertRulePrices($table, $batch);
        }

        return $result;
    }

    /**
     * Products the index has to cover for a website: those with rule data, plus those that only
     * have rows left in the price table from a rule that no longer applies to them.
     *
     * @return int[]
     */
    protected function _getIndexedProductIds(int $websiteId): array
    {
        $ids = $this->_connection->fetchCol(
            $this->_connection->select()
                ->distinct()
                ->from(['rp' => $this->_resource->getTable('catalogrule/rule_product')], ['product_id'])
                ->where('rp.website_id = ?', $websiteId),
        );

        $ids = array_merge($ids, $this->_connection->fetchCol(
            $this->_connection->select()
                ->distinct()
                ->from($this->_resource->getTable('catalogrule/rule_product_price'), ['product_id'])
                ->where('website_id = ?', $websiteId),
        ));

        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);

        return $ids;
    }

    /**
     * @param int[] $productIds
     * @return array<string, array{float, ?string, ?string}> keyed by product:group:date
     */
    protected function _loadStoredPrices(string $table, int $websiteId, array $productIds): array
    {
        $stored = [];
        $rows = $this->_connection->fetchAll(
            $this->_connection->select()
                ->from($table, ['product_id', 'customer_group_id', 'rule_date', 'rule_price',
                    'latest_start_date', 'earliest_end_date'])
                ->where('website_id = ?', $websiteId)
                ->where('product_id IN (?)', $productIds),
        );

        foreach ($rows as $row) {
            $stored[$this->_priceKey($row)] = [
                (float) $row['rule_price'],
                $row['latest_start_date'],
                $row['earliest_end_date'],
            ];
        }

        return $stored;
    }

    /**
     * Queue the rows of a finished product/customer group pair, leaving out the ones already stored
     *
     * @param array $pending
     * @param array<string, array> $group
     * @param array<string, array> $stored
     * @param array<string, true> $written
     * @param array<string, true> $replaced
     */
    protected function _stageGroup(array &$pending, array $group, array $stored, array &$written, array &$replaced): void
    {
        foreach ($group as $row) {
            $key = $this->_priceKey($row);
            $written[$key] = true;

            if (isset($stored[$key])) {
                if ($this->_isStoredPrice($stored[$key], $row)) {
                    continue;
                }
                $replaced[$key] = true;
            }

            $pending[] = $row;
        }
    }

    /**
     * Whether the stored row already holds what the rules produce now
     */
    protected function _isStoredPrice(array $stored, array $row): bool
    {
        return sprintf('%.4F', $stored[0]) === sprintf('%.4F', $row['rule_price'])
            && $stored[1] === ($row['latest_start_date'] ?: null)
            && $stored[2] === ($row['earliest_end_date'] ?: null);
    }

    /**
     * Delete the rows that are gone or whose value is being replaced
     *
     * @param string[] $keys
     */
    protected function _removePrices(string $table, int $websiteId, array $keys): void
    {
        $conditions = [];
        foreach ($keys as $key) {
            [$productId, $customerGroupId, $ruleDate] = explode(':', $key);
            $conditions[] = sprintf(
                "(product_id = %d AND customer_group_id = %d AND rule_date = '%s')",
                $productId,
                $customerGroupId,
                $ruleDate,
            );

            if (count($conditions) >= self::INDEX_INSERT_BATCH) {
                $this->_connection->query(
                    "DELETE FROM $table WHERE website_id = $websiteId AND (" . implode(' OR ', $conditions) . ')',
                );
                $conditions = [];
            }
        }

        if ($conditions) {
            $this->_connection->query(
                "DELETE FROM $table WHERE website_id = $websiteId AND (" . implode(' OR ', $conditions) . ')',
            );
        }
    }

    /**
     * @param array{product_id: int|string, customer_group_id: int|string, rule_date: string} $row
     */
    protected function _priceKey(array $row): string
    {
        return $row['product_id'] . ':' . $row['customer_group_id'] . ':' . $row['rule_date'];
    }

    /**
     * Yesterday, today and tomorrow relative to the reindex time, as 'Y-m-d' => timestamp.
     *
     * Three days are indexed so a store stays correct across the timezone spread of its websites.
     *
     * @return array<string, int>
     */
    protected function _getRuleDates(int $time): array
    {
        $locale = $this->_app->getLocale();

        $dates = [];
        foreach ([$time - 86400, $time, $time + 86400] as $timestamp) {
            $dates[$locale->formatDateForDb($timestamp, withTime: false)] = $timestamp;
        }
        return $dates;
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function _isRuleActiveAt(array $row, int $timestamp): bool
    {
        $toTime = (int) $row['to_time'];
        return $timestamp >= (int) $row['from_time'] && ($toTime === 0 || $timestamp <= $toTime);
    }

    /**
     * Apply one rule action to the price the chain has reached so far.
     *
     * @param array<string, mixed> $row
     */
    protected function _applyRuleAction(float $price, array $row): float
    {
        $amount = (float) $row['action_amount'];

        return match ($row['action_operator']) {
            'to_percent' => $price * $amount / 100,
            'by_percent' => $price * (1 - $amount / 100),
            'to_fixed'   => min($amount, $price),
            'by_fixed'   => max(0.0, $price - $amount),
            default      => $price,
        };
    }

    /**
     * Keep the cheapest price per date, with the widest start and narrowest end the rules allow.
     *
     * @param array<string, array<string, mixed>> $group
     * @param array<string, mixed> $row
     */
    protected function _collectRulePrice(array &$group, string $date, array $row, float $price, int $websiteId): void
    {
        if (!isset($group[$date])) {
            $group[$date] = [
                'rule_date'         => $date,
                'customer_group_id' => (int) $row['customer_group_id'],
                'product_id'        => (int) $row['product_id'],
                'rule_price'        => $price,
                'website_id'        => $websiteId,
                'latest_start_date' => $row['from_date'] ?: null,
                'earliest_end_date' => $row['to_date'] ?: null,
            ];
            return;
        }

        $bucket = &$group[$date];
        $bucket['rule_price'] = min($bucket['rule_price'], $price);

        if ($row['from_date']
            && ($bucket['latest_start_date'] === null || $row['from_date'] > $bucket['latest_start_date'])
        ) {
            $bucket['latest_start_date'] = $row['from_date'];
        }
        if ($row['to_date']
            && ($bucket['earliest_end_date'] === null || $row['to_date'] < $bucket['earliest_end_date'])
        ) {
            $bucket['earliest_end_date'] = $row['to_date'];
        }
    }

    /**
     * Write a batch of computed prices.
     *
     * The rows are built by _collectRulePrice from integers, floats and dates the
     * code generated itself, so the statement is assembled once per batch instead
     * of being prepared and bound value by value: on a catalogue of 10k products
     * the binding alone was a third of the refresh time.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    protected function _insertRulePrices(string $table, array $rows): void
    {
        $values = [];
        foreach ($rows as $row) {
            $values[] = sprintf(
                "('%s',%d,%d,%.4F,%d,%s,%s)",
                $row['rule_date'],
                (int) $row['customer_group_id'],
                (int) $row['product_id'],
                (float) $row['rule_price'],
                (int) $row['website_id'],
                $row['latest_start_date'] === null ? 'NULL' : "'" . $row['latest_start_date'] . "'",
                $row['earliest_end_date'] === null ? 'NULL' : "'" . $row['earliest_end_date'] . "'",
            );
        }

        $this->_connection->query(
            'INSERT INTO ' . $table
            . ' (rule_date, customer_group_id, product_id, rule_price, website_id,'
            . ' latest_start_date, earliest_end_date) VALUES ' . implode(',', $values),
        );
    }

    /**
     * Reindex catalog prices by website for timestamp
     *
     * @param int $timestamp
     */
    protected function _reindex(Mage_Core_Model_Website $website, $timestamp)
    {
        // No rate, no price to discount: the website is not selling, so it keeps no rule prices either
        if ($this->_websitePriceRate($website) === null) {
            $this->_removeOldIndexData($website);
            return;
        }

        $this->_fillIndexData($website, $timestamp);
    }

    protected function _websitePriceRate(Mage_Core_Model_Website $website): ?float
    {
        $store = $website->getDefaultStore();
        $helper = $this->_factory->getHelper('catalog');
        if (!$store || !$helper instanceof Mage_Catalog_Helper_Data) {
            return null;
        }

        return $helper->getWebsitePriceRate($store);
    }

    /**
     * Prepare data for group website relation
     * @param int $timestamp
     */
    protected function _prepareGroupWebsite($timestamp)
    {
        $this->_connection->delete($this->_resource->getTable('catalogrule/rule_group_website'), []);
        $select = $this->_connection->select()
            ->distinct()
            ->from(
                $this->_resource->getTable('catalogrule/rule_product'),
                ['rule_id', 'customer_group_id', 'website_id'],
            )
            ->where(new Maho\Db\Expr(((int) $timestamp) . ' >= from_time'))
            // A CASE returning either 1 or a comparison mixes integer and boolean, which PostgreSQL
            // rejects outright; the open-ended window is a plain disjunction anyway.
            ->where(new Maho\Db\Expr('to_time = 0 OR ' . ((int) $timestamp) . ' <= to_time'));
        $query = $select->insertFromSelect($this->_resource->getTable('catalogrule/rule_group_website'));
        $this->_connection->query($query);
    }

    /**
     * Return data for affected product
     */
    protected function _getProduct()
    {
        return null;
    }

    /**
     * Prepare affected product
     */
    protected function _prepareAffectedProduct()
    {
        $modelCondition = $this->_factory->getModel('catalog/product_condition');

        $productCondition = $modelCondition->setTable($this->_resource->getTable('catalogrule/affected_product'))
            ->setPkFieldName('product_id');

        $this->_app->dispatchEvent(
            'catalogrule_after_apply',
            [
                'product' => $this->_getProduct(),
                'product_condition' => $productCondition,
            ],
        );

        $this->_connection->delete($this->_resource->getTable('catalogrule/affected_product'));
    }
}
