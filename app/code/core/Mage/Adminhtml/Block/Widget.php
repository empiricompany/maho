<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024-2025 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Base widget class
 *
 * @package    Mage_Adminhtml
 *
 * @method $this setHeaderCss(string $value)
 * @method $this setTitle(string $value)
 */
class Mage_Adminhtml_Block_Widget extends Mage_Adminhtml_Block_Template
{
    /**
     * @return string
     */
    #[\Override]
    public function getId()
    {
        if ($this->getData('id') === null) {
            $this->setData('id', Mage::helper('core')->uniqHash('id_'));
        }
        return $this->getData('id');
    }

    /**
     * @return string
     */
    public function getHtmlId()
    {
        return $this->getId();
    }

    /**
     * Get current url
     *
     * @param array $params url parameters
     * @return string current url
     */
    public function getCurrentUrl($params = [])
    {
        if (!isset($params['_current'])) {
            $params['_current'] = true;
        }
        return $this->getUrl('*/*/*', $params);
    }

    protected function _addBreadcrumb($label, $title = null, $link = null)
    {
        /** @var Mage_Adminhtml_Block_Widget_Breadcrumbs $block */
        $block = $this->getLayout()->getBlock('breadcrumbs');
        $block->addLink($label, $title, $link);
    }

    /**
     * @return string
     */
    public function getGlobalIcon()
    {
        $title = $this->__('This attribute shares the same value in all the stores');
        return "<span title=\"$title\" class=\"attribute-global\">{$this->getIconSvg('link')}</span>";
    }
}
