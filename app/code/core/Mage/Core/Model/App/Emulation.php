<?php

/**
 * Maho
 *
 * @package    Mage_Core
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2023 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Core_Model_App_Emulation extends Varien_Object
{
    /**
     * Factory instance
     *
     * @var Mage_Core_Model_Factory
     */
    protected $_factory;

    /**
     * Application instance
     *
     * @var Mage_Core_Model_App
     */
    protected $_app;

    public function __construct(array $args = [])
    {
        $this->_factory = !empty($args['factory']) ? $args['factory'] : Mage::getSingleton('core/factory');
        $this->_app = !empty($args['app']) ? $args['app'] : Mage::app();
        unset($args['factory'], $args['app']);
        parent::__construct($args);
    }

    /**
     * Start environment emulation of the specified store
     *
     * Function returns information about initial store environment and emulates environment of another store
     *
     * @param int $storeId
     * @param string $area
     * @param bool $emulateStoreInlineTranslation emulate inline translation of the specified store or just disable it
     *
     * @return Varien_Object information about environment of the initial store
     */
    public function startEnvironmentEmulation(
        $storeId,
        $area = Mage_Core_Model_App_Area::AREA_FRONTEND,
        $emulateStoreInlineTranslation = false,
    ) {
        if (is_null($area)) {
            $area = Mage_Core_Model_App_Area::AREA_FRONTEND;
        }
        if ($emulateStoreInlineTranslation) {
            $initialTranslateInline = $this->_emulateInlineTranslation($storeId, $area);
        } else {
            $initialTranslateInline = $this->_emulateInlineTranslation();
        }
        $initialDesign = $this->_emulateDesign($storeId, $area);
        // Current store needs to be changed right before locale change and after design change
        $this->_app->setCurrentStore($storeId);
        $initialLocaleCode = $this->_emulateLocale($storeId, $area);

        $initialEnvironmentInfo = new Varien_Object();
        $initialEnvironmentInfo->setInitialTranslateInline($initialTranslateInline)
            ->setInitialDesign($initialDesign)
            ->setInitialLocaleCode($initialLocaleCode);

        Mage::app()->getTranslator()->init($area, true);

        return $initialEnvironmentInfo;
    }

    /**
     * Stop enviromment emulation
     *
     * Function restores initial store environment
     *
     * @param Varien_Object $initialEnvironmentInfo information about environment of the initial store
     *
     * @return $this
     */
    public function stopEnvironmentEmulation(Varien_Object $initialEnvironmentInfo)
    {
        $this->_restoreInitialInlineTranslation($initialEnvironmentInfo->getInitialTranslateInline());
        $initialDesign = $initialEnvironmentInfo->getInitialDesign();
        $this->_restoreInitialDesign($initialDesign);
        // Current store needs to be changed right before locale change and after design change
        $this->_app->setCurrentStore($initialDesign['store']);
        $this->_restoreInitialLocale($initialEnvironmentInfo->getInitialLocaleCode(), $initialDesign['area']);
        return $this;
    }

    /**
     * Emulate inline translation of the specified store
     *
     * Function disables inline translation if $storeId is null
     *
     * @param int|null $storeId
     * @param string $area
     *
     * @return bool initial inline translation state
     */
    protected function _emulateInlineTranslation($storeId = null, $area = Mage_Core_Model_App_Area::AREA_FRONTEND)
    {
        if (is_null($storeId)) {
            $newTranslateInline = false;
        } else {
            if ($area == Mage_Core_Model_App_Area::AREA_ADMINHTML) {
                $newTranslateInline = Mage::getStoreConfigFlag('dev/translate_inline/active_admin', $storeId);
            } else {
                $newTranslateInline = Mage::getStoreConfigFlag('dev/translate_inline/active', $storeId);
            }
        }
        $translateModel = Mage::getSingleton('core/translate');
        $initialTranslateInline = $translateModel->getTranslateInline();
        $translateModel->setTranslateInline($newTranslateInline);
        return $initialTranslateInline;
    }

    /**
     * Apply design of the specified store
     *
     * @param int $storeId
     * @param string $area
     *
     * @return array initial design parameters(package, store, area)
     */
    protected function _emulateDesign($storeId, $area = Mage_Core_Model_App_Area::AREA_FRONTEND)
    {
        $initialDesign = Mage::getDesign()->setAllGetOld([
            'package' => $this->_getStoreConfig('design/package/name', $storeId),
            'store'   => $storeId,
            'area'    => $area,
        ]);
        Mage::getDesign()->setTheme('');
        Mage::getDesign()->setPackageName('');
        return $initialDesign;
    }

    /**
     * Apply locale of the specified store
     *
     * @param null|string|bool|int|Mage_Core_Model_Store $storeId
     * @param string $area
     *
     * @return string initial locale code
     */
    protected function _emulateLocale($storeId, $area = Mage_Core_Model_App_Area::AREA_FRONTEND)
    {
        $initialLocaleCode = $this->_app->getLocale()->getLocaleCode();
        $newLocaleCode = $this->_getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, $storeId);
        if ($initialLocaleCode != $newLocaleCode) {
            $this->_app->getLocale()->setLocaleCode($newLocaleCode);
            $this->_factory->getSingleton('core/translate')->setLocale($newLocaleCode)->init($area, true);
        }
        return $initialLocaleCode;
    }

    /**
     * Retrieve config value for store by path
     *
     * @param string $path
     * @param null|string|bool|int|Mage_Core_Model_Store $store
     * @return mixed
     */
    protected function _getStoreConfig($path, $store = null)
    {
        return Mage::getStoreConfig($path, $store);
    }

    /**
     * Restore initial inline translation state
     *
     * @param bool $initialTranslateInline
     *
     * @return $this
     */
    protected function _restoreInitialInlineTranslation($initialTranslateInline)
    {
        $translateModel = Mage::getSingleton('core/translate');
        $translateModel->setTranslateInline($initialTranslateInline);
        return $this;
    }

    /**
     * Restore design of the initial store
     *
     *
     * @return $this
     */
    protected function _restoreInitialDesign(array $initialDesign)
    {
        Mage::getDesign()->setAllGetOld($initialDesign);
        Mage::getDesign()->setTheme('');
        Mage::getDesign()->setPackageName('');
        return $this;
    }

    /**
     * Restore locale of the initial store
     *
     * @param string $initialLocaleCode
     * @param string $initialArea
     *
     * @return $this
     */
    protected function _restoreInitialLocale(
        $initialLocaleCode,
        $initialArea = Mage_Core_Model_App_Area::AREA_ADMINHTML,
    ) {
        $currentLocaleCode = $this->_app->getLocale()->getLocaleCode();
        if ($currentLocaleCode != $initialLocaleCode) {
            $this->_app->getLocale()->setLocaleCode($initialLocaleCode);
            $this->_factory->getSingleton('core/translate')->setLocale($initialLocaleCode)->init($initialArea, true);
        }
        return $this;
    }
}
