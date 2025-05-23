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
 * Adminhtml tag accordion
 *
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Tag_Edit_Assigned extends Mage_Adminhtml_Block_Widget_Accordion
{
    /**
     * Add Assigned products accordion to layout
     *
     */
    #[\Override]
    protected function _prepareLayout()
    {
        if (is_null(Mage::registry('current_tag')->getId())) {
            return $this;
        }

        $tagModel = Mage::registry('current_tag');

        $this->setId('tag_assigned_grid');

        $this->addItem('tag_assign', [
            'title'         => Mage::helper('tag')->__('Products Tagged by Administrators'),
            'ajax'          => true,
            'content_url'   => $this->getUrl('*/*/assigned', ['ret' => 'all', 'tag_id' => $tagModel->getId(), 'store' => $tagModel->getStoreId()]),
        ]);
        return parent::_prepareLayout();
    }
}
