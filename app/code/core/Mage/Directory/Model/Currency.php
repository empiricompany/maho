<?php

/**
 * Maho
 *
 * @package    Mage_Directory
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Currency model
 *
 * @package    Mage_Directory
 *
 * @method Mage_Directory_Model_Resource_Currency _getResource()
 * @method $this unsRate()
 */
class Mage_Directory_Model_Currency extends Mage_Core_Model_Abstract
{
    /**
     * CONFIG path constant: ALLOW
    */
    public const XML_PATH_CURRENCY_ALLOW   = 'currency/options/allow';
    /**
     * CONFIG path constant: DEFAULT
     */
    public const XML_PATH_CURRENCY_DEFAULT = 'currency/options/default';
    /**
     * CONFIG path constant: BASE
     */
    public const XML_PATH_CURRENCY_BASE    = 'currency/options/base';

    /**
     * @var Mage_Directory_Model_Currency_Filter - currency filter
     */
    protected $_filter;

    /**
     * Currency Rates
     *
     * @var array
     */
    protected $_rates;

    /**
     * Class constructor
     */
    #[\Override]
    protected function _construct()
    {
        $this->_init('directory/currency');
    }

    /**
     * Get currency code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->_getData('currency_code');
    }

    /**
     * Get currency code
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->_getData('currency_code');
    }

    /**
     * Currency Rates getter
     *
     * @return array
     */
    public function getRates()
    {
        return $this->_rates;
    }

    /**
     * Currency Rates setter
     *
     * @param array $rates Currency Rates
     * @return $this
     */
    public function setRates(array $rates)
    {
        $this->_rates = $rates;
        return $this;
    }

    /**
     * Loading currency data
     *
     * @param   string $id
     * @param   string $field
     * @return  $this
     */
    #[\Override]
    public function load($id, $field = null)
    {
        $this->unsRate();
        $this->setData('currency_code', $id);
        return $this;
    }

    /**
     * Get currency rate (only base=>allowed)
     *
     * @param string|Mage_Directory_Model_Currency $toCurrency
     * @return float|int
     * @throws Mage_Core_Exception
     */
    public function getRate($toCurrency)
    {
        if (is_string($toCurrency)) {
            $code = $toCurrency;
        } elseif ($toCurrency instanceof self) {
            $code = $toCurrency->getCurrencyCode();
        } else {
            throw Mage::exception('Mage_Directory', Mage::helper('directory')->__('Invalid target currency.'));
        }
        $rates = $this->getRates();
        if (!isset($rates[$code])) {
            $rates[$code] = $this->_getResource()->getRate($this->getCode(), $toCurrency);
            $this->setRates($rates);
        }
        return $rates[$code];
    }

    /**
     * Get currency rate (base=>allowed or allowed=>base)
     *
     * @param string|Mage_Directory_Model_Currency $toCurrency
     * @return float
     * @throws Mage_Core_Exception
     */
    public function getAnyRate($toCurrency)
    {
        if (is_string($toCurrency)) {
            $code = $toCurrency;
        } elseif ($toCurrency instanceof Mage_Directory_Model_Currency) {
            $code = $toCurrency->getCurrencyCode();
        } else {
            throw Mage::exception('Mage_Directory', Mage::helper('directory')->__('Invalid target currency.'));
        }
        $rates = $this->getRates();
        if (!isset($rates[$code])) {
            $rates[$code] = $this->_getResource()->getAnyRate($this->getCode(), $toCurrency);
            $this->setRates($rates);
        }
        return $rates[$code];
    }

    /**
     * Convert price to currency format
     *
     * @param float $price
     * @param null|string|Mage_Directory_Model_Currency $toCurrency
     * @return float
     * @throws Exception
     */
    public function convert($price, $toCurrency = null)
    {
        if (is_null($toCurrency)) {
            return $price;
        } else {
            $rate = $this->getRate($toCurrency);
            if ($rate) {
                return $price * $rate;
            }
        }

        throw new Exception(Mage::helper('directory')->__(
            'Undefined rate from "%s-%s".',
            $this->getCode(),
            $toCurrency instanceof Mage_Directory_Model_Currency ? $toCurrency->getCode() : $toCurrency,
        ));
    }

    /**
     * Get currency filter
     *
     * @return Mage_Directory_Model_Currency_Filter
     */
    public function getFilter()
    {
        if (!$this->_filter) {
            $this->_filter = new Mage_Directory_Model_Currency_Filter($this->getCode());
        }

        return $this->_filter;
    }

    /**
     * Format price to currency format
     *
     * @param float $price
     * @param array $options
     * @param bool $includeContainer
     * @param bool $addBrackets
     * @return string
     */
    public function format($price, $options = [], $includeContainer = true, $addBrackets = false)
    {
        return $this->formatPrecision($price, 2, $options, $includeContainer, $addBrackets);
    }

    /**
     * Apply currency format to number with specific rounding precision
     *
     * @param   float $price
     * @param   int $precision
     * @param   array $options
     * @param   bool $includeContainer
     * @param   bool $addBrackets
     * @return  string
     */
    public function formatPrecision(
        $price,
        $precision,
        $options = [],
        $includeContainer = true,
        $addBrackets = false,
    ) {
        if (!isset($options['precision'])) {
            $options['precision'] = $precision;
        }
        if ($includeContainer) {
            return '<span class="price">' . ($addBrackets ? '[' : '') . $this->formatTxt($price, $options) .
                ($addBrackets ? ']' : '') . '</span>';
        }
        return $this->formatTxt($price, $options);
    }

    /**
     * Returns the formatted price
     *
     * @param float $price
     * @param null|array $options
     * @return string
     */
    public function formatTxt($price, $options = [])
    {
        if (!is_numeric($price)) {
            $price = Mage::app()->getLocale()->getNumber($price);
        }
        /**
         * Fix problem with 12 000 000, 1 200 000
         *
         * %f - the argument is treated as a float, and presented as a floating-point number (locale aware).
         * %F - the argument is treated as a float, and presented as a floating-point number (non-locale aware).
         */
        $price = sprintf('%F', $price);
        if ($price == -0) {
            $price = 0;
        }
        return Mage::app()->getLocale()->currency($this->getCode())->toCurrency($price, $options);
    }

    /**
     * Returns the formatting template for numbers
     *
     * @return string
     */
    public function getOutputFormat()
    {
        $formated = $this->formatTxt(0);
        $number = $this->formatTxt(0, ['display' => Zend_Currency::NO_SYMBOL]);
        return str_replace($number, '%s', $formated);
    }

    /**
     * Retrieve allowed currencies according to config
     *
     * @return array
     */
    public function getConfigAllowCurrencies()
    {
        $allowedCurrencies = $this->_getResource()->getConfigCurrencies($this, self::XML_PATH_CURRENCY_ALLOW);
        $appBaseCurrencyCode = Mage::app()->getBaseCurrencyCode();
        if (!in_array($appBaseCurrencyCode, $allowedCurrencies)) {
            $allowedCurrencies[] = $appBaseCurrencyCode;
        }
        foreach (Mage::app()->getStores() as $store) {
            $code = $store->getBaseCurrencyCode();
            if (!in_array($code, $allowedCurrencies)) {
                $allowedCurrencies[] = $code;
            }
        }

        return $allowedCurrencies;
    }

    /**
     * Retrieve default currencies according to config
     *
     * @return array
     */
    public function getConfigDefaultCurrencies()
    {
        return $this->_getResource()->getConfigCurrencies($this, self::XML_PATH_CURRENCY_DEFAULT);
    }

    /**
     * Retrieve base currencies according to config
     *
     * @return array
     */
    public function getConfigBaseCurrencies()
    {
        return $this->_getResource()->getConfigCurrencies($this, self::XML_PATH_CURRENCY_BASE);
    }

    /**
     * Retrieve currency rates to other currencies
     *
     * @param array|string|Mage_Directory_Model_Currency $currency
     * @param array $toCurrencies
     * @return array
     */
    public function getCurrencyRates($currency, $toCurrencies = null)
    {
        if ($currency instanceof Mage_Directory_Model_Currency) {
            $currency = $currency->getCode();
        }
        return $this->_getResource()->getCurrencyRates($currency, $toCurrencies);
    }

    /**
     * Save currency rates
     *
     * @param array $rates
     * @return object
     */
    public function saveRates($rates)
    {
        $this->_getResource()->saveRates($rates);
        return $this;
    }
}
