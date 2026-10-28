<?php

/**
 * An order for the transactional email preview, with items that were never saved.
 *
 * The real model reads its items through a collection built from the database, which a preview
 * has no reason to touch: this one returns the items it was given.
 *
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Mage_Adminhtml
 */

declare(strict_types=1);

class Mage_Adminhtml_Model_Email_Template_Preview_Order extends Mage_Sales_Model_Order
{
    /** @var list<Mage_Sales_Model_Order_Item> */
    protected array $_previewItems = [];

    /**
     * @param list<Mage_Sales_Model_Order_Item> $items
     * @return $this
     */
    public function setPreviewItems(array $items)
    {
        $this->_previewItems = $items;
        return $this;
    }

    /**
     * @return list<Mage_Sales_Model_Order_Item>
     */
    #[\Override]
    public function getAllItems()
    {
        return $this->_previewItems;
    }
}
