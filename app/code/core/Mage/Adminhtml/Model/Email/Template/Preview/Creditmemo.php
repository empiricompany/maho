<?php

/**
 * A credit memo for the transactional email preview, with items that were never saved.
 *
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Mage_Adminhtml
 */

declare(strict_types=1);

class Mage_Adminhtml_Model_Email_Template_Preview_Creditmemo extends Mage_Sales_Model_Order_Creditmemo
{
    /** @var list<Mage_Sales_Model_Order_Creditmemo_Item> */
    protected array $_previewItems = [];

    /**
     * @param list<Mage_Sales_Model_Order_Creditmemo_Item> $items
     * @return $this
     */
    public function setPreviewItems(array $items)
    {
        $this->_previewItems = $items;
        return $this;
    }

    /**
     * @return list<Mage_Sales_Model_Order_Creditmemo_Item>
     */
    #[\Override]
    public function getAllItems()
    {
        return $this->_previewItems;
    }
}
