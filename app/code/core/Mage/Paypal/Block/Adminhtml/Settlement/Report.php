<?php

/**
 * Maho
 *
 * @package    Mage_Paypal
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2021-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Adminhtml paypal settlement reports grid block
 *
 * @package    Mage_Paypal
 */
class Mage_Paypal_Block_Adminhtml_Settlement_Report extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'paypal';
        $this->_controller = 'adminhtml_settlement_report';
        $this->_headerText = Mage::helper('paypal')->__('PayPal Settlement Reports');
        parent::__construct();
        $this->_removeButton('add');
        $this->_addButton('fetch', [
            'label'   => Mage::helper('paypal')->__('Fetch Updates'),
            'onclick' => Mage::helper('core/js')->getConfirmSetLocationJs(
                $this->getUrl('*/*/fetch'),
                Mage::helper('paypal')->__('Connecting to PayPal SFTP server to fetch new reports. Are you sure you want to proceed?'),
            ),
            'class'   => 'task',
        ]);
    }
}
