<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2025 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml page
 *
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Page extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('page.phtml');
        $action = Mage::app()->getFrontController()->getAction();
        if ($action) {
            $this->addBodyClass($action->getFullActionName('-'));
        }
    }

    /**
     * Get current language
     *
     * @return string
     */
    public function getLang()
    {
        if (!$this->hasData('lang')) {
            $this->setData('lang', substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2));
        }
        return $this->getData('lang');
    }

    /**
     * Add CSS class to page body tag
     *
     * @param string $className
     * @return $this
     */
    public function addBodyClass($className)
    {
        $className = preg_replace('#[^a-z0-9]+#', '-', strtolower($className));
        $this->setBodyClass($this->getBodyClass() . ' ' . $className);
        return $this;
    }

    public function getBodyAttributes(): string
    {
        $attributes = '';
        if ($class = $this->getBodyClass()) {
            $attributes .= " class=\"{$this->escapeHtml($class)}\"";
        }
        if ($theme = Mage::app()->getCookie()->get('maho_admin_theme')) {
            $attributes .= " data-maho-theme=\"{$this->escapeHtml($theme)}\"";
        }
        return $attributes;
    }

    public function getContainerCssClass(): string
    {
        $blockLeft = $this->getLayout()->getBlock('left');
        if ($blockLeft->getIsCollapsed() || $blockLeft->countChildren() === 0) {
            return 'container-collapsed';
        }
        return 'container';
    }
}
