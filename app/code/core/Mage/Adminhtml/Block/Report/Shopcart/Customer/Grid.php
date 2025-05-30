<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml items in carts report grid block
 *
 * @package    Mage_Adminhtml
 *
 * @method Mage_Reports_Model_Resource_Customer_Collection getCollection()
 */
class Mage_Adminhtml_Block_Report_Shopcart_Customer_Grid extends Mage_Adminhtml_Block_Report_Grid_Shopcart
{
    /**
     * Mage_Adminhtml_Block_Report_Shopcart_Customer_Grid constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('grid');
    }

    #[\Override]
    protected function _prepareCollection()
    {
        //TODO: add full name logic
        $collection = Mage::getResourceModel('reports/customer_collection')
          ->addAttributeToSelect('firstname')
          ->addAttributeToSelect('lastname');

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    #[\Override]
    protected function _afterLoadCollection()
    {
        $this->getCollection()->addCartInfo();
        return parent::_afterLoadCollection();
    }

    #[\Override]
    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', [
            'header'    => Mage::helper('reports')->__('ID'),
            'index'     => 'entity_id',
        ]);

        $this->addColumn('firstname', [
            'header'    => Mage::helper('reports')->__('First Name'),
            'index'     => 'firstname',
        ]);

        $this->addColumn('lastname', [
            'header'    => Mage::helper('reports')->__('Last Name'),
            'index'     => 'lastname',
        ]);

        $this->addColumn('items', [
            'header'    => Mage::helper('reports')->__('Items in Cart'),
            'width'     => '70px',
            'sortable'  => false,
            'align'     => 'right',
            'index'     => 'items',
        ]);

        $currencyCode = $this->getCurrentCurrencyCode();

        $this->addColumn('total', [
            'header'    => Mage::helper('reports')->__('Total'),
            'width'     => '70px',
            'sortable'  => false,
            'type'      => 'currency',
            'currency_code' => $currencyCode,
            'index'     => 'total',
            'renderer'  => 'adminhtml/report_grid_column_renderer_currency',
            'rate'          => $this->getRate($currencyCode),
        ]);

        $this->setFilterVisibility(false);

        $this->addExportType('*/*/exportCustomerCsv', Mage::helper('reports')->__('CSV'));
        $this->addExportType('*/*/exportCustomerExcel', Mage::helper('reports')->__('Excel XML'));

        return parent::_prepareColumns();
    }
}
