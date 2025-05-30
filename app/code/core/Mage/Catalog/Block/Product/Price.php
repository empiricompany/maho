<?php

/**
 * Maho
 *
 * @package    Mage_Catalog
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Product price block
 *
 * @package    Mage_Catalog
 *
 * @method $this setPriceElementIdPrefix(string $value)
 * @method bool hasRealPriceHtml()
 * @method string getRealPriceHtml()
 * @method $this setRealPriceHtml(string $value)
 */
class Mage_Catalog_Block_Product_Price extends Mage_Catalog_Block_Product_Abstract
{
    /**
     * Price display type
     *
     * @var int
     */
    protected $_priceDisplayType = null;

    /**
     * The id suffix
     *
     * @var string
     */
    protected $_idSuffix = '';

    /**
     * Retrieve product
     *
     * @return Mage_Catalog_Model_Product
     */
    #[\Override]
    public function getProduct()
    {
        $product = $this->_getData('product');
        if (!$product) {
            $product = Mage::registry('product');
        }
        return $product;
    }

    /**
     * Returns the product's minimal price
     *
     * @return float
     */
    public function getDisplayMinimalPrice()
    {
        return $this->_getData('display_minimal_price');
    }

    /**
     * Sets the id suffix
     *
     * @param string $idSuffix
     * @return $this
     */
    public function setIdSuffix($idSuffix)
    {
        $this->_idSuffix = $idSuffix;
        return $this;
    }

    /**
     * Returns the id suffix
     *
     * @return string
     */
    public function getIdSuffix()
    {
        return $this->_idSuffix;
    }

    /**
     * Get tier prices (formatted)
     *
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Catalog_Model_Product $parent
     * @return array
     */
    #[\Override]
    public function getTierPrices($product = null, $parent = null)
    {
        if (is_null($product)) {
            $product = $this->getProduct();
        }
        $prices = $product->getFormatedTierPrice();

        // if our parent is a bundle, then we need to further adjust our tier prices
        if (isset($parent) && $parent->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            /** @var Mage_Bundle_Model_Product_Price $bundlePriceModel */
            $bundlePriceModel = Mage::getModel('bundle/product_price');
        }

        $res = [];
        if (is_array($prices)) {
            foreach ($prices as $price) {
                $price['price_qty'] = $price['price_qty'] * 1;

                $productPrice = $product->getPrice();
                if ($product->getPrice() != $product->getFinalPrice()) {
                    $productPrice = $product->getFinalPrice();
                }

                // Group price must be used for percent calculation if it is lower
                $groupPrice = $product->getGroupPrice();
                if ($productPrice > $groupPrice) {
                    $productPrice = $groupPrice;
                }

                if ($price['price'] < $productPrice) {
                    // use the original prices to determine the percent savings
                    $price['savePercent'] = ceil(100 - round((100 / $productPrice) * $price['price']));

                    // if applicable, adjust the tier prices
                    if (isset($bundlePriceModel)) {
                        $price['price']         = $bundlePriceModel->getLowestPrice($parent, $price['price']);
                        $price['website_price'] = $bundlePriceModel->getLowestPrice($parent, $price['website_price']);
                    }

                    $tierPrice = Mage::app()->getStore()->convertPrice(
                        Mage::helper('tax')->getPrice($product, $price['website_price']),
                    );
                    $price['formated_price'] = Mage::app()->getStore()->formatPrice($tierPrice);
                    $price['formated_price_incl_tax'] = Mage::app()->getStore()->formatPrice(
                        Mage::app()->getStore()->convertPrice(
                            Mage::helper('tax')->getPrice($product, $price['website_price'], true),
                        ),
                    );

                    if (Mage::helper('catalog')->canApplyMsrp($product)) {
                        $oldPrice = $product->getFinalPrice();
                        $product->setPriceCalculation(false);
                        $product->setPrice($tierPrice);
                        $product->setFinalPrice($tierPrice);

                        $this->getLayout()->getBlock('product.info')->getPriceHtml($product);
                        $product->setPriceCalculation(true);

                        $price['real_price_html'] = $product->getRealPriceHtml();
                        $product->setFinalPrice($oldPrice);
                    }

                    $res[] = $price;
                }
            }
        }

        return $res;
    }

    /**
     * Retrieve url for direct adding product to cart
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $additional
     * @return string
     */
    #[\Override]
    public function getAddToCartUrl($product, $additional = [])
    {
        return $this->getAddToCartUrlCustom($product, $additional);
    }

    /**
     * Prevent displaying if the price is not available
     *
     * @return string
     */
    #[\Override]
    protected function _toHtml()
    {
        if (!$this->getProduct() || $this->getProduct()->getCanShowPrice() === false) {
            return '';
        }
        return parent::_toHtml();
    }

    /**
     * Get Product Price valid JS string
     *
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    public function getRealPriceJs($product)
    {
        $html = $this->hasRealPriceHtml() ? $this->getRealPriceHtml() : $product->getRealPriceHtml();
        return Mage::helper('core')->jsonEncode($html);
    }

    /**
     * Retrieve block cache tags
     *
     * @return array
     */
    #[\Override]
    public function getCacheTags()
    {
        return array_merge(parent::getCacheTags(), $this->getProduct()->getCacheIdTags());
    }

    /**
     * Retrieve attribute instance by name, id or config node
     *
     * If attribute is not found false is returned
     *
     * @param string|int|Mage_Core_Model_Config_Element $attribute
     * @return Mage_Eav_Model_Entity_Attribute_Abstract | false
     */
    public function getProductAttribute($attribute)
    {
        return $this->getProduct()->getResource()->getAttribute($attribute);
    }

    /**
     * Retrieve url for direct adding product to cart with or without Form Key
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $additional
     * @param bool $addFormKey
     * @return string
     */
    #[\Override]
    public function getAddToCartUrlCustom($product, $additional = [], $addFormKey = true)
    {
        /** @var Mage_Checkout_Helper_Cart $helper */
        $helper = $this->helper('checkout/cart');

        if (!$addFormKey) {
            return $helper->getAddUrlCustom($product, $additional, false);
        }
        return $helper->getAddUrl($product, $additional);
    }
}
