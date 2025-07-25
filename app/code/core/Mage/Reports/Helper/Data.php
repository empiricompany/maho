<?php

/**
 * Maho
 *
 * @package    Mage_Reports
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2025 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2025 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Reports_Helper_Data extends Mage_Core_Helper_Abstract
{
    public const REPORT_PERIOD_TYPE_DAY    = 'day';
    public const REPORT_PERIOD_TYPE_MONTH  = 'month';
    public const REPORT_PERIOD_TYPE_YEAR   = 'year';

    public const XML_PATH_REPORTS_ENABLED  = 'reports/general/enabled';

    protected $_moduleName = 'Mage_Reports';

    /**
     * Return reports flag enabled.
     *
     * @return bool
     */

    public function isReportsEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_REPORTS_ENABLED);
    }

    public function isRecentlyViewedEnabled(): bool
    {
        return Mage::getStoreConfigFlag('catalog/recently_products/enabled_recently_viewed');
    }

    public function isProductCompareEnabled(): bool
    {
        return Mage::getStoreConfigFlag('catalog/recently_products/enabled_product_compare');
    }

    /**
     * Retrieve array of intervals
     *
     * @param string $from
     * @param string $to
     * @param self::REPORT_PERIOD_TYPE_* $period
     * @return array
     * @throws Zend_Date_Exception
     */
    public function getIntervals($from, $to, $period = self::REPORT_PERIOD_TYPE_DAY)
    {
        $intervals = [];
        $dateStart = null;

        if (!$from && !$to) {
            return $intervals;
        }

        $start = new Zend_Date($from, Varien_Date::DATE_INTERNAL_FORMAT);

        if ($period == self::REPORT_PERIOD_TYPE_DAY) {
            $dateStart = $start;
        }

        if ($period == self::REPORT_PERIOD_TYPE_MONTH) {
            $dateStart = new Zend_Date(date('Y-m', $start->getTimestamp()), Varien_Date::DATE_INTERNAL_FORMAT);
        }

        if ($period == self::REPORT_PERIOD_TYPE_YEAR) {
            $dateStart = new Zend_Date(date('Y', $start->getTimestamp()), Varien_Date::DATE_INTERNAL_FORMAT);
        }

        if (!$period || !$dateStart) {
            return $intervals;
        }

        $dateEnd = new Zend_Date($to, Varien_Date::DATE_INTERNAL_FORMAT);

        while ($dateStart->compare($dateEnd) <= 0) {
            $time = '';
            switch ($period) {
                case self::REPORT_PERIOD_TYPE_DAY:
                    $time = $dateStart->toString('yyyy-MM-dd');
                    $dateStart->addDay(1);
                    break;
                case self::REPORT_PERIOD_TYPE_MONTH:
                    $time = $dateStart->toString('yyyy-MM');
                    $dateStart->addMonth(1);
                    break;
                case self::REPORT_PERIOD_TYPE_YEAR:
                    $time = $dateStart->toString('yyyy');
                    $dateStart->addYear(1);
                    break;
            }
            $intervals[] = $time;
        }
        return  $intervals;
    }

    /**
     * @param Varien_Data_Collection $collection
     * @param string $from
     * @param string $to
     * @param string $periodType
     */
    public function prepareIntervalsCollection($collection, $from, $to, $periodType = self::REPORT_PERIOD_TYPE_DAY)
    {
        $intervals = $this->getIntervals($from, $to, $periodType);

        foreach ($intervals as $interval) {
            $item = Mage::getModel('adminhtml/report_item');
            $item->setPeriod($interval);
            $item->setIsEmpty();
            $collection->addItem($item);
        }
    }
}
