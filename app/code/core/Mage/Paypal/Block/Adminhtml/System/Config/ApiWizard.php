<?php

/**
 * Maho
 *
 * @package    Mage_Paypal
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2023 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Custom renderer for PayPal API credentials wizard popup
 *
 * @package    Mage_Paypal
 */
class Mage_Paypal_Block_Adminhtml_System_Config_ApiWizard extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @var string
     */
    protected $_wizardTemplate = 'paypal/system/config/api_wizard.phtml';

    /**
     * Set template to itself
     */
    #[\Override]
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate($this->_wizardTemplate);
        }
        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @return string
     */
    #[\Override]
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @return string
     */
    #[\Override]
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $originalData = $element->getOriginalData();
        $elementHtmlId = $element->getHtmlId();
        $this->addData(array_merge(
            $this->_getButtonData($elementHtmlId, $originalData),
            $this->_getSandboxButtonData($elementHtmlId, $originalData),
        ));
        return $this->_toHtml();
    }

    /**
     * Prepare button data
     *
     * @param string $elementHtmlId
     * @param array $originalData
     * @return array
     */
    protected function _getButtonData($elementHtmlId, $originalData)
    {
        return [
            'button_label' => Mage::helper('paypal')->__($originalData['button_label']),
            'button_url'   => $originalData['button_url'],
            'html_id' => $elementHtmlId,
        ];
    }

    /**
     * Prepare sandbox button data
     *
     * @param string $elementHtmlId
     * @param array $originalData
     * @return array
     */
    protected function _getSandboxButtonData($elementHtmlId, $originalData)
    {
        return [
            'sandbox_button_label' => Mage::helper('paypal')->__($originalData['sandbox_button_label']),
            'sandbox_button_url'   => $originalData['sandbox_button_url'],
            'sandbox_html_id' => 'sandbox_' . $elementHtmlId,
        ];
    }
}
