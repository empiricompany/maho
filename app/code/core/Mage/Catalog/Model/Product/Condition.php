<?php

/**
 * Maho
 *
 * @package    Mage_Catalog
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Class Mage_Catalog_Model_Product_Condition
 *
 * @package    Mage_Catalog
 *
 * @method string getTable()
 * @method $this setTable(string $tableName)
 * @method string getPkFieldName()
 * @method $this setPkFieldName(string $fieldName)
 */
class Mage_Catalog_Model_Product_Condition extends Varien_Object implements Mage_Catalog_Model_Product_Condition_Interface
{
    /**
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     * @return $this
     */
    #[\Override]
    public function applyToCollection($collection)
    {
        if ($this->getTable() && $this->getPkFieldName()) {
            $collection->joinTable(
                $this->getTable(),
                $this->getPkFieldName() . '=entity_id',
                ['affected_product_id' => $this->getPkFieldName()],
            );
        }
        return $this;
    }

    /**
     * @param Varien_Db_Adapter_Pdo_Mysql $dbAdapter
     * @return string|Varien_Db_Select
     */
    #[\Override]
    public function getIdsSelect($dbAdapter)
    {
        if ($this->getTable() && $this->getPkFieldName()) {
            return $dbAdapter->select()
                ->from($this->getTable(), $this->getPkFieldName());
        }
        return '';
    }
}
