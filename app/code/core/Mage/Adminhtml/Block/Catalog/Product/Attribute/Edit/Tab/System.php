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
 * Product attribute add/edit form system tab
 *
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Catalog_Product_Attribute_Edit_Tab_System extends Mage_Adminhtml_Block_Widget_Form
{
    #[\Override]
    protected function _prepareForm()
    {
        $model = Mage::registry('entity_attribute');

        $form = new Varien_Data_Form();
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => Mage::helper('catalog')->__('System Properties')]);

        if ($model->getAttributeId()) {
            $fieldset->addField('attribute_id', 'hidden', [
                'name' => 'attribute_id',
            ]);
        }

        $yesno = [
            [
                'value' => 0,
                'label' => Mage::helper('catalog')->__('No'),
            ],
            [
                'value' => 1,
                'label' => Mage::helper('catalog')->__('Yes'),
            ]];

        /*$fieldset->addField('attribute_model', 'text', array(
            'name' => 'attribute_model',
            'label' => Mage::helper('catalog')->__('Attribute Model'),
            'title' => Mage::helper('catalog')->__('Attribute Model'),
        ));

        $fieldset->addField('backend_model', 'text', array(
            'name' => 'backend_model',
            'label' => Mage::helper('catalog')->__('Backend Model'),
            'title' => Mage::helper('catalog')->__('Backend Model'),
        ));*/

        $fieldset->addField('backend_type', 'select', [
            'name' => 'backend_type',
            'label' => Mage::helper('catalog')->__('Data Type for Saving in Database'),
            'title' => Mage::helper('catalog')->__('Data Type for Saving in Database'),
            'options' => [
                'text'      => Mage::helper('catalog')->__('Text'),
                'varchar'   => Mage::helper('catalog')->__('Varchar'),
                'static'    => Mage::helper('catalog')->__('Static'),
                'datetime'  => Mage::helper('catalog')->__('Datetime'),
                'decimal'   => Mage::helper('catalog')->__('Decimal'),
                'int'       => Mage::helper('catalog')->__('Integer'),
            ],
        ]);

        /*$fieldset->addField('backend_table', 'text', array(
            'name' => 'backend_table',
            'label' => Mage::helper('catalog')->__('Backend Table'),
            'title' => Mage::helper('catalog')->__('Backend Table Title'),
        ));

        $fieldset->addField('frontend_model', 'text', array(
            'name' => 'frontend_model',
            'label' => Mage::helper('catalog')->__('Frontend Model'),
            'title' => Mage::helper('catalog')->__('Frontend Model'),
        ));*/

        /*$fieldset->addField('is_visible', 'select', array(
            'name' => 'is_visible',
            'label' => Mage::helper('catalog')->__('Visible'),
            'title' => Mage::helper('catalog')->__('Visible'),
            'values' => $yesno,
        ));*/

        /*$fieldset->addField('source_model', 'text', array(
            'name' => 'source_model',
            'label' => Mage::helper('catalog')->__('Source Model'),
            'title' => Mage::helper('catalog')->__('Source Model'),
        ));*/

        $fieldset->addField('is_global', 'select', [
            'name'  => 'is_global',
            'label' => Mage::helper('catalog')->__('Globally Editable'),
            'title' => Mage::helper('catalog')->__('Globally Editable'),
            'values' => $yesno,
        ]);

        $form->setValues($model->getData());

        if ($model->getAttributeId()) {
            $form->getElement('backend_type')->setDisabled(1);
            if ($model->getIsGlobal()) {
                #$form->getElement('is_global')->setDisabled(1);
            }
        } else {
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
