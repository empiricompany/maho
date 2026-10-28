<?php

/**
 * A shipment for the transactional email preview, with items that were never saved.
 *
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Mage_Adminhtml
 */

declare(strict_types=1);

class Mage_Adminhtml_Model_Email_Template_Preview_Shipment extends Mage_Sales_Model_Order_Shipment
{
    /** @var list<Mage_Sales_Model_Order_Shipment_Item> */
    protected array $_previewItems = [];

    /** @var list<Mage_Sales_Model_Order_Shipment_Track> */
    protected array $_previewTracks = [];

    /**
     * @param list<Mage_Sales_Model_Order_Shipment_Item> $items
     * @return $this
     */
    public function setPreviewItems(array $items)
    {
        $this->_previewItems = $items;
        return $this;
    }

    /**
     * @param list<Mage_Sales_Model_Order_Shipment_Track> $tracks
     * @return $this
     */
    public function setPreviewTracks(array $tracks)
    {
        $this->_previewTracks = $tracks;
        return $this;
    }

    /**
     * @return list<Mage_Sales_Model_Order_Shipment_Item>
     */
    #[\Override]
    public function getAllItems()
    {
        return $this->_previewItems;
    }

    /**
     * @return list<Mage_Sales_Model_Order_Shipment_Track>
     */
    #[\Override]
    public function getAllTracks($reload = false)
    {
        return $this->_previewTracks;
    }
}
