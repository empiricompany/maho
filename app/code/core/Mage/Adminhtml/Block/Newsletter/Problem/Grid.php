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
 * Adminhtml newsletter problem grid block
 *
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Newsletter_Problem_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('problemGrid');
        $this->setSaveParametersInSession(true);
        $this->setMessageBlockVisibility(true);
        $this->setUseAjax(true);
        $this->setEmptyText(Mage::helper('newsletter')->__('No problems found.'));
    }

    #[\Override]
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('newsletter/problem_collection')
            ->addSubscriberInfo()
            ->addQueueInfo();

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    #[\Override]
    protected function _prepareColumns()
    {
        $this->addColumn('checkbox', [
            'sortable'     => false,
            'filter'    => 'adminhtml/newsletter_problem_grid_filter_checkbox',
            'renderer'  => 'adminhtml/newsletter_problem_grid_renderer_checkbox',
            'width'     => '20px',
        ]);

        $this->addColumn('problem_id', [
            'header' => Mage::helper('newsletter')->__('ID'),
            'index'  => 'problem_id',
            'width'  => '50px',
        ]);

        $this->addColumn('subscriber', [
            'header' => Mage::helper('newsletter')->__('Subscriber'),
            'index'  => 'subscriber_id',
            'format' => '#$subscriber_id $customer_name ($subscriber_email)',
        ]);

        $this->addColumn('queue_start', [
            'header' => Mage::helper('newsletter')->__('Queue Date Start'),
            'index'  => 'queue_start_at',
            'gmtoffset' => true,
            'type'   => 'datetime',
        ]);

        $this->addColumn('queue', [
            'header' => Mage::helper('newsletter')->__('Queue Subject'),
            'index'  => 'template_subject',
        ]);

        $this->addColumn('problem_code', [
            'header' => Mage::helper('newsletter')->__('Error Code'),
            'index'  => 'problem_error_code',
            'type'   => 'number',
        ]);

        $this->addColumn('problem_text', [
            'header' => Mage::helper('newsletter')->__('Error Text'),
            'index'  => 'problem_error_text',
        ]);
        return parent::_prepareColumns();
    }
}
