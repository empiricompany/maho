<?php

/**
 * Maho
 *
 * @package    Mage_Paypal
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Source model for available logo types
 *
 * @package    Mage_Paypal
 */
class Mage_Paypal_Model_System_Config_Source_Logo
{
    public function toOptionArray()
    {
        $result = ['' => Mage::helper('paypal')->__('No Logo')];
        $result += Mage::getModel('paypal/config')->getAdditionalOptionsLogoTypes();
        return $result;
    }
}
