<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml config system template source
 *
 * @package    Mage_Adminhtml
 *
 * @method string getPath()
 */
class Mage_Adminhtml_Model_System_Config_Source_Email_Template extends Varien_Object
{
    /**
     * Config xpath to email template node
     */
    public const XML_PATH_TEMPLATE_EMAIL = 'global/template/email/';

    /**
     * Generate list of email templates
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$collection = Mage::registry('config_system_email_template')) {
            $collection = Mage::getResourceModel('core/email_template_collection')
                ->load();

            Mage::register('config_system_email_template', $collection);
        }
        $options = $collection->toOptionArray();
        $templateName = Mage::helper('adminhtml')->__('Default Template from Locale');
        $nodeName = str_replace('/', '_', $this->getPath());
        $templateLabelNode = Mage::app()->getConfig()->getNode(self::XML_PATH_TEMPLATE_EMAIL . $nodeName . '/label');
        if ($templateLabelNode) {
            $templateName = Mage::helper('adminhtml')->__((string) $templateLabelNode);
            $templateName = Mage::helper('adminhtml')->__('%s (Default Template from Locale)', $templateName);
        }
        array_unshift(
            $options,
            [
                'value' => $nodeName,
                'label' => $templateName,
            ],
        );
        return $options;
    }
}
