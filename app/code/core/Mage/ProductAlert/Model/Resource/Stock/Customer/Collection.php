<?php

/**
 * Maho
 *
 * @package    Mage_ProductAlert
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * ProductAlert Stock Customer collection
 *
 * @package    Mage_ProductAlert
 */
class Mage_ProductAlert_Model_Resource_Stock_Customer_Collection extends Mage_Customer_Model_Resource_Customer_Collection
{
    /**
     * join productalert stock data to customer collection
     *
     * @param int $productId
     * @param int $websiteId
     * @return $this
     */
    public function join($productId, $websiteId)
    {
        $this->getSelect()->join(
            ['alert' => $this->getTable('productalert/stock')],
            'alert.customer_id=e.entity_id',
            ['alert_stock_id', 'add_date', 'send_date', 'send_count', 'status'],
        );

        $this->getSelect()->where('alert.product_id=?', $productId);
        if ($websiteId) {
            $this->getSelect()->where('alert.website_id=?', $websiteId);
        }
        $this->_setIdFieldName('alert_stock_id');
        $this->addAttributeToSelect('*');

        return $this;
    }
}
