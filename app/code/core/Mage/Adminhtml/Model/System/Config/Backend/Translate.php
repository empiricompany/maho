<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * System config translate inline fields backend model
 *
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Model_System_Config_Backend_Translate extends Mage_Core_Model_Config_Data
{
    /**
     * Path to config node with list of caches
     *
     * @var string
     */
    public const XML_PATH_INVALID_CACHES = 'dev/translate_inline/invalid_caches';

    /**
     * Set status 'invalidate' for blocks and other output caches
     *
     * @return $this
     */
    #[\Override]
    protected function _afterSave()
    {
        $types = array_keys(Mage::getStoreConfig(self::XML_PATH_INVALID_CACHES));
        if ($this->isValueChanged()) {
            Mage::app()->getCacheInstance()->invalidateType($types);
        }

        return $this;
    }
}
