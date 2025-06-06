<?php

/**
 * Maho
 *
 * @package    Mage_Api2
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Block tabs for attributes edit page
 *
 * @package    Mage_Api2
 */
class Mage_Api2_Block_Adminhtml_Attribute_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('api2_attribute_section_main')
            ->setDestElementId('edit_form')
            ->setData('title', $this->__('ACL Attributes Information'));
    }
}
