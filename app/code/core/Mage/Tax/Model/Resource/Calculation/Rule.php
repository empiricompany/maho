<?php

/**
 * Maho
 *
 * @package    Mage_Tax
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Tax rate resource model
 *
 * @package    Mage_Tax
 */
class Mage_Tax_Model_Resource_Calculation_Rule extends Mage_Core_Model_Resource_Db_Abstract
{
    #[\Override]
    protected function _construct()
    {
        $this->_init('tax/tax_calculation_rule', 'tax_calculation_rule_id');
    }

    /**
     * Initialize unique fields
     *
     * @return $this
     */
    #[\Override]
    protected function _initUniqueFields()
    {
        $this->_uniqueFields = [[
            'field' => ['code'],
            'title' => Mage::helper('tax')->__('Code'),
        ]];
        return $this;
    }

    /**
     * Fetches rules by rate, customer tax class and product tax class
     * Returns array of rule codes
     *
     * @param array $rateId
     * @param array $customerTaxClassId
     * @param array $productTaxClassId
     * @return array
     */
    public function fetchRuleCodes($rateId, $customerTaxClassId, $productTaxClassId)
    {
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from(['main' => $this->getTable('tax/tax_calculation')], null)
            ->joinLeft(
                ['d' => $this->getTable('tax/tax_calculation_rule')],
                'd.tax_calculation_rule_id = main.tax_calculation_rule_id',
                ['d.code'],
            )
            ->where('main.tax_calculation_rate_id in (?)', $rateId)
            ->where('main.customer_tax_class_id in (?)', $customerTaxClassId)
            ->where('main.product_tax_class_id in (?)', $productTaxClassId)
            ->distinct(true);

        return $adapter->fetchCol($select);
    }
}
