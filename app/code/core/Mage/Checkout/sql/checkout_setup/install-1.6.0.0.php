<?php

/**
 * Maho
 *
 * @package    Mage_Checkout
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2017-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/** @var Mage_Checkout_Model_Resource_Setup $this */
$installer = $this;
$installer->startSetup();

/**
 * Create table 'checkout/agreement'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('checkout/agreement'))
    ->addColumn('agreement_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Agreement Id')
    ->addColumn('name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, [
    ], 'Name')
    ->addColumn('content', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', [
    ], 'Content')
    ->addColumn('content_height', Varien_Db_Ddl_Table::TYPE_TEXT, 25, [
    ], 'Content Height')
    ->addColumn('checkbox_text', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', [
    ], 'Checkbox Text')
    ->addColumn('is_active', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'nullable'  => false,
        'default'   => '0',
    ], 'Is Active')
    ->addColumn('is_html', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'nullable'  => false,
        'default'   => '0',
    ], 'Is Html')
    ->setComment('Checkout Agreement');
$installer->getConnection()->createTable($table);

/**
 * Create table 'checkout/agreement_store'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('checkout/agreement_store'))
    ->addColumn('agreement_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Agreement Id')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Store Id')
    ->addForeignKey(
        $installer->getFkName('checkout/agreement_store', 'agreement_id', 'checkout/agreement', 'agreement_id'),
        'agreement_id',
        $installer->getTable('checkout/agreement'),
        'agreement_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE,
    )
    ->addForeignKey(
        $installer->getFkName('checkout/agreement_store', 'store_id', 'core/store', 'store_id'),
        'store_id',
        $installer->getTable('core/store'),
        'store_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE,
    )
    ->setComment('Checkout Agreement Store');
$installer->getConnection()->createTable($table);

$setup = $installer->getConnection();

$select = $setup->select()
    ->from($installer->getTable('core/config_data'), 'COUNT(*)')
    ->where('path=?', 'customer/address/prefix_show')
    ->where('value NOT LIKE ?', '0');
$showPrefix = (bool) Mage::helper('customer/address')->getConfig('prefix_show')
    || ($setup->fetchOne($select) > 0);

$select = $setup->select()
    ->from($installer->getTable('core/config_data'), 'COUNT(*)')
    ->where('path=?', 'customer/address/middlename_show')
    ->where('value NOT LIKE ?', '0');
$showMiddlename = (bool) Mage::helper('customer/address')->getConfig('middlename_show')
    || ($setup->fetchOne($select) > 0);

$select = $setup->select()
    ->from($installer->getTable('core/config_data'), 'COUNT(*)')
    ->where('path=?', 'customer/address/suffix_show')
    ->where('value NOT LIKE ?', '0');
$showSuffix = (bool) Mage::helper('customer/address')->getConfig('suffix_show')
    || ($setup->fetchOne($select) > 0);

$select = $setup->select()
    ->from($installer->getTable('core/config_data'), 'COUNT(*)')
    ->where('path=?', 'customer/address/dob_show')
    ->where('value NOT LIKE ?', '0');
$showDob = (bool) Mage::helper('customer/address')->getConfig('dob_show')
    || ($setup->fetchOne($select) > 0);

$select = $setup->select()
    ->from($installer->getTable('core/config_data'), 'COUNT(*)')
    ->where('path=?', 'customer/address/taxvat_show')
    ->where('value NOT LIKE ?', '0');
$showTaxVat = (bool) Mage::helper('customer/address')->getConfig('taxvat_show')
    || ($setup->fetchOne($select) > 0);

$customerEntityTypeId = $installer->getEntityTypeId('customer');
$addressEntityTypeId  = $installer->getEntityTypeId('customer_address');

/**
 *****************************************************************************
 * checkout/onepage/register
 *****************************************************************************
 */

$setup->insert($installer->getTable('eav/form_type'), [
    'code'      => 'checkout_onepage_register',
    'label'     => 'checkout_onepage_register',
    'is_system' => 1,
    'theme'     => '',
    'store_id'  => 0,
]);
$formTypeId   = $setup->lastInsertId($installer->getTable('eav/form_type'));

$setup->insert($installer->getTable('eav/form_type_entity'), [
    'type_id'        => $formTypeId,
    'entity_type_id' => $customerEntityTypeId,
]);
$setup->insert($installer->getTable('eav/form_type_entity'), [
    'type_id'        => $formTypeId,
    'entity_type_id' => $addressEntityTypeId,
]);

$elementSort = 0;
if ($showPrefix) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => null,
        'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'prefix'),
        'sort_order'    => $elementSort++,
    ]);
}
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'firstname'),
    'sort_order'    => $elementSort++,
]);
if ($showMiddlename) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => null,
        'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'middlename'),
        'sort_order'    => $elementSort++,
    ]);
}
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'lastname'),
    'sort_order'    => $elementSort++,
]);
if ($showSuffix) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => null,
        'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'suffix'),
        'sort_order'    => $elementSort++,
    ]);
}
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'company'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($customerEntityTypeId, 'email'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'street'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'city'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'region'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'postcode'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'country_id'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'telephone'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'fax'),
    'sort_order'    => $elementSort++,
]);
if ($showDob) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => null,
        'attribute_id'  => $installer->getAttributeId($customerEntityTypeId, 'dob'),
        'sort_order'    => $elementSort++,
    ]);
}
if ($showTaxVat) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => null,
        'attribute_id'  => $installer->getAttributeId($customerEntityTypeId, 'taxvat'),
        'sort_order'    => $elementSort++,
    ]);
}

/**
 *****************************************************************************
 * checkout/onepage/register_guest
 *****************************************************************************
 */

$setup->insert($installer->getTable('eav/form_type'), [
    'code'      => 'checkout_onepage_register_guest',
    'label'     => 'checkout_onepage_register_guest',
    'is_system' => 1,
    'theme'     => '',
    'store_id'  => 0,
]);
$formTypeId   = $setup->lastInsertId($installer->getTable('eav/form_type'));

$setup->insert($installer->getTable('eav/form_type_entity'), [
    'type_id'        => $formTypeId,
    'entity_type_id' => $customerEntityTypeId,
]);
$setup->insert($installer->getTable('eav/form_type_entity'), [
    'type_id'        => $formTypeId,
    'entity_type_id' => $addressEntityTypeId,
]);

$elementSort = 0;
if ($showPrefix) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => null,
        'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'prefix'),
        'sort_order'    => $elementSort++,
    ]);
}
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'firstname'),
    'sort_order'    => $elementSort++,
]);
if ($showMiddlename) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => null,
        'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'middlename'),
        'sort_order'    => $elementSort++,
    ]);
}
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'lastname'),
    'sort_order'    => $elementSort++,
]);
if ($showSuffix) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => null,
        'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'suffix'),
        'sort_order'    => $elementSort++,
    ]);
}
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'company'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($customerEntityTypeId, 'email'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'street'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'city'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'region'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'postcode'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'country_id'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'telephone'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'fax'),
    'sort_order'    => $elementSort++,
]);
if ($showDob) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => null,
        'attribute_id'  => $installer->getAttributeId($customerEntityTypeId, 'dob'),
        'sort_order'    => $elementSort++,
    ]);
}
if ($showTaxVat) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => null,
        'attribute_id'  => $installer->getAttributeId($customerEntityTypeId, 'taxvat'),
        'sort_order'    => $elementSort++,
    ]);
}

/**
 *****************************************************************************
 * checkout/onepage/billing_address
 *****************************************************************************
 */

$setup->insert($installer->getTable('eav/form_type'), [
    'code'      => 'checkout_onepage_billing_address',
    'label'     => 'checkout_onepage_billing_address',
    'is_system' => 1,
    'theme'     => '',
    'store_id'  => 0,
]);
$formTypeId   = $setup->lastInsertId($installer->getTable('eav/form_type'));

$setup->insert($installer->getTable('eav/form_type_entity'), [
    'type_id'        => $formTypeId,
    'entity_type_id' => $addressEntityTypeId,
]);

$elementSort = 0;
if ($showPrefix) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => null,
        'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'prefix'),
        'sort_order'    => $elementSort++,
    ]);
}
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'firstname'),
    'sort_order'    => $elementSort++,
]);
if ($showMiddlename) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => null,
        'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'middlename'),
        'sort_order'    => $elementSort++,
    ]);
}
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'lastname'),
    'sort_order'    => $elementSort++,
]);
if ($showSuffix) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => null,
        'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'suffix'),
        'sort_order'    => $elementSort++,
    ]);
}
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'company'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'street'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'city'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'region'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'postcode'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'country_id'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'telephone'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'fax'),
    'sort_order'    => $elementSort++,
]);

/**
 *****************************************************************************
 * checkout/onepage/shipping_address
 *****************************************************************************
 */

$setup->insert($installer->getTable('eav/form_type'), [
    'code'      => 'checkout_onepage_shipping_address',
    'label'     => 'checkout_onepage_shipping_address',
    'is_system' => 1,
    'theme'     => '',
    'store_id'  => 0,
]);
$formTypeId   = $setup->lastInsertId($installer->getTable('eav/form_type'));

$setup->insert($installer->getTable('eav/form_type_entity'), [
    'type_id'        => $formTypeId,
    'entity_type_id' => $addressEntityTypeId,
]);

$elementSort = 0;
if ($showPrefix) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => null,
        'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'prefix'),
        'sort_order'    => $elementSort++,
    ]);
}
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'firstname'),
    'sort_order'    => $elementSort++,
]);
if ($showMiddlename) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => null,
        'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'middlename'),
        'sort_order'    => $elementSort++,
    ]);
}
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'lastname'),
    'sort_order'    => $elementSort++,
]);
if ($showSuffix) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => null,
        'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'suffix'),
        'sort_order'    => $elementSort++,
    ]);
}
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'company'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'street'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'city'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'region'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'postcode'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'country_id'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'telephone'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => null,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'fax'),
    'sort_order'    => $elementSort++,
]);

/**
 *****************************************************************************
 * checkout/multishipping/register/
 *****************************************************************************
 */

$setup->insert($installer->getTable('eav/form_type'), [
    'code'      => 'checkout_multishipping_register',
    'label'     => 'checkout_multishipping_register',
    'is_system' => 1,
    'theme'     => '',
    'store_id'  => 0,
]);
$formTypeId   = $setup->lastInsertId($installer->getTable('eav/form_type'));

$setup->insert($installer->getTable('eav/form_type_entity'), [
    'type_id'        => $formTypeId,
    'entity_type_id' => $customerEntityTypeId,
]);
$setup->insert($installer->getTable('eav/form_type_entity'), [
    'type_id'        => $formTypeId,
    'entity_type_id' => $addressEntityTypeId,
]);

$setup->insert($installer->getTable('eav/form_fieldset'), [
    'type_id'    => $formTypeId,
    'code'       => 'general',
    'sort_order' => 1,
]);
$fieldsetId = $setup->lastInsertId($installer->getTable('eav/form_fieldset'));

$setup->insert($installer->getTable('eav/form_fieldset_label'), [
    'fieldset_id' => $fieldsetId,
    'store_id'    => 0,
    'label'       => 'Personal Information',
]);

$elementSort = 0;
if ($showPrefix) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => $fieldsetId,
        'attribute_id'  => $installer->getAttributeId($customerEntityTypeId, 'prefix'),
        'sort_order'    => $elementSort++,
    ]);
}
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => $fieldsetId,
    'attribute_id'  => $installer->getAttributeId($customerEntityTypeId, 'firstname'),
    'sort_order'    => $elementSort++,
]);
if ($showMiddlename) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => $fieldsetId,
        'attribute_id'  => $installer->getAttributeId($customerEntityTypeId, 'middlename'),
        'sort_order'    => $elementSort++,
    ]);
}
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => $fieldsetId,
    'attribute_id'  => $installer->getAttributeId($customerEntityTypeId, 'lastname'),
    'sort_order'    => $elementSort++,
]);
if ($showSuffix) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => $fieldsetId,
        'attribute_id'  => $installer->getAttributeId($customerEntityTypeId, 'suffix'),
        'sort_order'    => $elementSort++,
    ]);
}
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => $fieldsetId,
    'attribute_id'  => $installer->getAttributeId($customerEntityTypeId, 'email'),
    'sort_order'    => $elementSort++,
]);
if ($showDob) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => $fieldsetId,
        'attribute_id'  => $installer->getAttributeId($customerEntityTypeId, 'dob'),
        'sort_order'    => $elementSort++,
    ]);
}
if ($showTaxVat) {
    $setup->insert($installer->getTable('eav/form_element'), [
        'type_id'       => $formTypeId,
        'fieldset_id'   => $fieldsetId,
        'attribute_id'  => $installer->getAttributeId($customerEntityTypeId, 'taxvat'),
        'sort_order'    => $elementSort++,
    ]);
}

$setup->insert($installer->getTable('eav/form_fieldset'), [
    'type_id'    => $formTypeId,
    'code'       => 'address',
    'sort_order' => 2,
]);
$fieldsetId = $setup->lastInsertId($installer->getTable('eav/form_fieldset'));

$setup->insert($installer->getTable('eav/form_fieldset_label'), [
    'fieldset_id' => $fieldsetId,
    'store_id'    => 0,
    'label'       => 'Address Information',
]);

$elementSort = 0;
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => $fieldsetId,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'company'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => $fieldsetId,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'telephone'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => $fieldsetId,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'street'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => $fieldsetId,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'city'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => $fieldsetId,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'region'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => $fieldsetId,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'postcode'),
    'sort_order'    => $elementSort++,
]);
$setup->insert($installer->getTable('eav/form_element'), [
    'type_id'       => $formTypeId,
    'fieldset_id'   => $fieldsetId,
    'attribute_id'  => $installer->getAttributeId($addressEntityTypeId, 'country_id'),
    'sort_order'    => $elementSort++,
]);

$table = $installer->getTable('core/config_data');

$select = $setup->select()
    ->from($table, ['config_id', 'value'])
    ->where('path = ?', 'checkout/options/onepage_checkout_disabled');

$data = $setup->fetchAll($select);

if ($data) {
    try {
        $setup->beginTransaction();

        foreach ($data as $value) {
            $bind = [
                'path'  => 'checkout/options/onepage_checkout_enabled',
                'value' => !((bool) $value['value']),
            ];
            $where = 'config_id = ' . $value['config_id'];
            $setup->update($table, $bind, $where);
        }

        $setup->commit();
    } catch (Exception $e) {
        $setup->rollBack();
        throw $e;
    }
}

$installer->endSetup();
