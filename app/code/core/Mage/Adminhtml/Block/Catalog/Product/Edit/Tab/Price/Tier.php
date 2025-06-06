<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml tier price item renderer
 *
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Price_Tier extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Price_Group_Abstract
{
    public function __construct()
    {
        $this->setTemplate('catalog/product/edit/price/tier.phtml');
    }

    /**
     * Retrieve list of initial customer groups
     *
     * @return array
     */
    #[\Override]
    protected function _getInitialCustomerGroups()
    {
        return [Mage_Customer_Model_Group::CUST_GROUP_ALL => Mage::helper('catalog')->__('ALL GROUPS')];
    }

    /**
     * Sort values
     *
     * @param array $data
     * @return array
     */
    #[\Override]
    protected function _sortValues($data)
    {
        usort($data, [$this, '_sortTierPrices']);
        return $data;
    }

    /**
     * Sort tier price values callback method
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function _sortTierPrices($a, $b)
    {
        if ($a['website_id'] != $b['website_id']) {
            return $a['website_id'] < $b['website_id'] ? -1 : 1;
        }
        if ($a['cust_group'] != $b['cust_group']) {
            return $this->getCustomerGroups($a['cust_group']) < $this->getCustomerGroups($b['cust_group']) ? -1 : 1;
        }
        if ($a['price_qty'] != $b['price_qty']) {
            return $a['price_qty'] < $b['price_qty'] ? -1 : 1;
        }

        return 0;
    }

    /**
     * Prepare global layout
     * Add "Add tier" button to layout
     *
     * @return $this
     */
    #[\Override]
    protected function _prepareLayout()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData([
                'label' => Mage::helper('catalog')->__('Add Tier'),
                'onclick' => 'return tierPriceControl.addItem()',
                'class' => 'add',
            ]);
        $button->setName('add_tier_price_item_button');

        $this->setChild('add_button', $button);
        return parent::_prepareLayout();
    }
}
