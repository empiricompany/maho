<?php

/**
 * Maho
 *
 * @package    Mage_Widget
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Widget Instance Settings tab block
 *
 * @package    Mage_Widget
 */
class Mage_Widget_Block_Adminhtml_Widget_Instance_Edit_Tab_Settings extends Mage_Adminhtml_Block_Widget_Form implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    #[\Override]
    protected function _construct()
    {
        parent::_construct();
        $this->setActive(true);
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    #[\Override]
    public function getTabLabel()
    {
        return Mage::helper('widget')->__('Settings');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    #[\Override]
    public function getTabTitle()
    {
        return Mage::helper('widget')->__('Settings');
    }

    /**
     * Returns status flag about this tab can be showen or not
     *
     * @return bool
     */
    #[\Override]
    public function canShowTab()
    {
        return !(bool) $this->getWidgetInstance()->isCompleteToCreate();
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return false
     */
    #[\Override]
    public function isHidden()
    {
        return false;
    }

    /**
     * Getter
     *
     * @return Mage_Widget_Model_Widget_Instance
     */
    public function getWidgetInstance()
    {
        return Mage::registry('current_widget_instance');
    }

    #[\Override]
    protected function _prepareForm()
    {
        $widgetInstance = $this->getWidgetInstance();
        $form = new Varien_Data_Form([
            'id' => 'edit_form',
            'action' => $this->getData('action'),
            'method' => 'post',
        ]);

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => Mage::helper('widget')->__('Settings')],
        );

        $this->_addElementTypes($fieldset);

        $fieldset->addField('type', 'select', [
            'name'     => 'type',
            'label'    => Mage::helper('widget')->__('Type'),
            'title'    => Mage::helper('widget')->__('Type'),
            'required' => true,
            'values'   => $this->getTypesOptionsArray(),
        ]);

        $fieldset->addField('package_theme', 'select', [
            'name'     => 'package_theme',
            'label'    => Mage::helper('widget')->__('Design Package/Theme'),
            'title'    => Mage::helper('widget')->__('Design Package/Theme'),
            'required' => true,
            'values'   => $this->getPackegeThemeOptionsArray(),
        ]);
        $continueButton = $this->getLayout()
            ->createBlock('adminhtml/widget_button')
            ->setData([
                'label'     => Mage::helper('widget')->__('Continue'),
                'onclick'   => "setSettings('" . $this->getContinueUrl() . "', 'type', 'package_theme')",
                'class'     => 'save',
            ]);
        $fieldset->addField('continue_button', 'note', [
            'text' => $continueButton->toHtml(),
        ]);

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Return url for continue button
     *
     * @return string
     */
    public function getContinueUrl()
    {
        return $this->getUrl('*/*/*', [
            '_current'  => true,
            'type'      => '{{type}}',
            'package'   => '{{package}}',
            'theme'     => '{{theme}}',
        ]);
    }

    /**
     * Retrieve array (widget_type => widget_name) of available widgets
     *
     * @return array
     */
    public function getTypesOptionsArray()
    {
        $widgets = $this->getWidgetInstance()->getWidgetsOptionArray();
        array_unshift($widgets, [
            'value' => '',
            'label' => Mage::helper('widget')->__('-- Please Select --'),
        ]);
        return $widgets;
    }

    /**
     * User-defined widgets sorting by Name
     *
     * @param array $a
     * @param array $b
     * @return int<-1, 1>
     */
    protected function _sortWidgets($a, $b)
    {
        return strcmp($a['label'], $b['label']);
    }

    /**
     * Retrieve package/theme options array
     *
     * @return array
     */
    public function getPackegeThemeOptionsArray()
    {
        return Mage::getModel('core/design_source_design')
            ->setIsFullLabel(true)->getAllOptions(true);
    }
}
