<?php

/**
 * Maho
 *
 * @package    Mage_Bundle
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2023 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Bundle option renderer
 *
 * @package    Mage_Bundle
 *
 * @method bool getCanEditPrice()
 * @method $this setCanEditPrice(bool $value)
 * @method bool getCanReadPrice()
 * @method $this setCanReadPrice(bool $value)
 */
class Mage_Bundle_Block_Adminhtml_Catalog_Product_Edit_Tab_Bundle_Option extends Mage_Adminhtml_Block_Widget
{
    /**
     * Form element
     *
     * @var Varien_Data_Form_Element_Abstract|null
     */
    protected $_element = null;

    /**
     * List of customer groups
     *
     * @deprecated since 1.7.0.0
     * @var array|null
     */
    protected $_customerGroups = null;

    /**
     * List of websites
     *
     * @deprecated since 1.7.0.0
     * @var array|null
     */
    protected $_websites = null;

    /**
     * List of bundle product options
     *
     * @var array|null
     */
    protected $_options = null;

    /**
     * Bundle option renderer class constructor
     *
     * Sets block template and necessary data
     */
    public function __construct()
    {
        $this->setTemplate('bundle/product/edit/bundle/option.phtml');
        $this->setCanReadPrice(true);
        $this->setCanEditPrice(true);
    }

    /**
     * @return string
     */
    public function getFieldId()
    {
        return 'bundle_option';
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return 'bundle_options';
    }

    /**
     * Retrieve Product object
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        if (!$this->getData('product')) {
            $this->setData('product', Mage::registry('product'));
        }
        return $this->getData('product');
    }

    /**
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        return $this->toHtml();
    }

    /**
     * @return $this
     */
    public function setElement(Varien_Data_Form_Element_Abstract $element)
    {
        $this->_element = $element;
        return $this;
    }

    /**
     * @return Varien_Data_Form_Element_Abstract|null
     */
    public function getElement()
    {
        return $this->_element;
    }

    /**
     * @return bool
     */
    public function isMultiWebsites()
    {
        return !Mage::app()->isSingleStoreMode();
    }

    /**
     * @return Mage_Adminhtml_Block_Widget
     */
    #[\Override]
    protected function _prepareLayout()
    {
        $this->setChild(
            'add_selection_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData([
                    'id'    => $this->getFieldId() . '_{{index}}_add_button',
                    'label'     => Mage::helper('bundle')->__('Add Selection'),
                    'on_click'   => 'bSelection.showSearch(event)',
                    'class' => 'add',
                ]),
        );

        $this->setChild(
            'close_search_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData([
                    'id'    => $this->getFieldId() . '_{{index}}_close_button',
                    'label'     => Mage::helper('bundle')->__('Close'),
                    'on_click'   => 'bSelection.closeSearch(event)',
                    'class' => 'back no-display',
                ]),
        );

        $this->setChild(
            'option_delete_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData([
                    'label' => Mage::helper('catalog')->__('Delete Option'),
                    'class' => 'delete delete-product-option',
                    'on_click' => 'bOption.remove(event)',
                ]),
        );

        $this->setChild(
            'selection_template',
            $this->getLayout()->createBlock('bundle/adminhtml_catalog_product_edit_tab_bundle_option_selection'),
        );

        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }

    /**
     * @return string
     */
    public function getCloseSearchButtonHtml()
    {
        return $this->getChildHtml('close_search_button');
    }

    /**
     * @return string
     */
    public function getAddSelectionButtonHtml()
    {
        return $this->getChildHtml('add_selection_button');
    }

    /**
     * Retrieve list of bundle product options
     *
     * @return array
     */
    public function getOptions()
    {
        if (!$this->_options) {
            $product = $this->getProduct();
            /** @var Mage_Bundle_Model_Product_Type $productType */
            $productType = $product->getTypeInstance(true);

            $productType->setStoreFilter(
                $product->getStoreId(),
                $product,
            );

            /** @var Mage_Bundle_Model_Resource_Option_Collection $optionCollection */
            $optionCollection = $productType->getOptionsCollection($product);

            /** @var Mage_Bundle_Model_Resource_Selection_Collection $selectionCollection */
            $selectionCollection = $productType->getSelectionsCollection(
                $productType->getOptionsIds($product),
                $product,
            );

            $this->_options = $optionCollection->appendSelections($selectionCollection);
            if ($this->getCanReadPrice() === false) {
                foreach ($this->_options as $option) {
                    if ($option->getSelections()) {
                        foreach ($option->getSelections() as $selection) {
                            $selection->setCanReadPrice($this->getCanReadPrice());
                            $selection->setCanEditPrice($this->getCanEditPrice());
                        }
                    }
                }
            }
        }
        return $this->_options;
    }

    /**
     * @return int
     */
    public function getAddButtonId()
    {
        return $this->getLayout()
                ->getBlock('admin.product.bundle.items')
                ->getChild('add_button')->getId();
    }

    /**
     * @return string
     */
    public function getOptionDeleteButtonHtml()
    {
        return $this->getChildHtml('option_delete_button');
    }

    /**
     * @return string
     */
    public function getSelectionHtml()
    {
        return $this->getChildHtml('selection_template');
    }

    /**
     * @return string
     */
    public function getTypeSelectHtml()
    {
        $select = $this->getLayout()->createBlock('adminhtml/html_select')
            ->setData([
                'id' => $this->getFieldId() . '_{{index}}_type',
                'class' => 'select select-product-option-type required-option-select',
                'extra_params' => 'onchange="bOption.changeType(event)"',
            ])
            ->setName($this->getFieldName() . '[{{index}}][type]')
            ->setOptions(Mage::getSingleton('bundle/source_option_type')->toOptionArray());

        return $select->getHtml();
    }

    /**
     * @return string
     */
    public function getRequireSelectHtml()
    {
        $select = $this->getLayout()->createBlock('adminhtml/html_select')
            ->setData([
                'id' => $this->getFieldId() . '_{{index}}_required',
                'class' => 'select',
            ])
            ->setName($this->getFieldName() . '[{{index}}][required]')
            ->setOptions(Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray());

        return $select->getHtml();
    }

    /**
     * @return bool
     */
    public function isDefaultStore()
    {
        return ($this->getProduct()->getStoreId() == '0');
    }
}
