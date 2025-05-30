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

use Mage_Adminhtml_Block_Widget_Grid_Massaction_Abstract as MassAction;

/**
 * Adminhtml sales orders grid
 *
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Sales_Invoice_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('sales_invoice_grid');
        $this->setUseAjax(true);
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * Retrieve collection class
     *
     * @return string
     */
    protected function _getCollectionClass()
    {
        return 'sales/order_invoice_grid_collection';
    }

    #[\Override]
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    #[\Override]
    protected function _prepareColumns()
    {
        $this->addColumn('increment_id', [
            'header'    => Mage::helper('sales')->__('Invoice #'),
            'index'     => 'increment_id',
            'type'      => 'text',
        ]);

        $this->addColumn('created_at', [
            'header'    => Mage::helper('sales')->__('Invoice Date'),
            'index'     => 'created_at',
            'type'      => 'datetime',
        ]);

        $this->addColumn('order_increment_id', [
            'header'    => Mage::helper('sales')->__('Order #'),
            'index'     => 'order_increment_id',
            'type'      => 'text',
            'escape'    => true,
        ]);

        $this->addColumn('order_created_at', [
            'header'    => Mage::helper('sales')->__('Order Date'),
            'index'     => 'order_created_at',
            'type'      => 'datetime',
        ]);

        $this->addColumn('billing_name', [
            'header' => Mage::helper('sales')->__('Bill to Name'),
            'index' => 'billing_name',
        ]);

        $this->addColumn('state', [
            'header'    => Mage::helper('sales')->__('Status'),
            'index'     => 'state',
            'type'      => 'options',
            'options'   => Mage::getModel('sales/order_invoice')->getStates(),
        ]);

        $this->addColumn('grand_total', [
            'header'    => Mage::helper('customer')->__('Amount'),
            'index'     => 'grand_total',
            'type'      => 'currency',
            'currency'  => 'order_currency_code',
        ]);

        $this->addColumn(
            'action',
            [
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => [
                    [
                        'caption' => Mage::helper('sales')->__('View'),
                        'url'     => ['base' => '*/sales_invoice/view'],
                        'field'   => 'invoice_id',
                    ],
                ],
                'is_system' => true,
            ],
        );

        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel XML'));

        return parent::_prepareColumns();
    }

    /**
     * @return $this
     */
    #[\Override]
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('invoice_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        $this->getMassactionBlock()->addItem(MassAction::PDF_INVOICE_ORDER, [
            'label' => Mage::helper('sales')->__('PDF Invoices'),
            'url'  => $this->getUrl('*/sales_invoice/pdfinvoices'),
        ]);

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order_Invoice $row
     * @return false|string
     */
    #[\Override]
    public function getRowUrl($row)
    {
        if (!Mage::getSingleton('admin/session')->isAllowed('sales/order/invoice')) {
            return false;
        }

        return $this->getUrl(
            '*/sales_invoice/view',
            [
                'invoice_id' => $row->getId(),
            ],
        );
    }

    /**
     * @return string
     */
    #[\Override]
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', ['_current' => true]);
    }
}
