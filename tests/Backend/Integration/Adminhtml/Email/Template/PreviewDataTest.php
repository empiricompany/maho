<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 */

declare(strict_types=1);

uses(Tests\MahoBackendTestCase::class);

describe('Transactional email preview data', function () {
    beforeEach(function () {
        $this->storeId = (int) Mage::app()->getDefaultStoreView()->getId();
        $this->variables = (new Mage_Adminhtml_Model_Email_Template_PreviewData())->getVariables($this->storeId);
    });

    it('builds a coherent order without reading the database', function () {
        $order = $this->variables['order'];

        expect($order->getAllItems())->not->toBeEmpty();
        expect($order->getIncrementId())->not->toBe('');
        expect($order->getBillingAddress()->getCity())->not->toBe('');
        expect($order->getShippingAddress()->getCountryId())->not->toBe('');

        $expected = round(
            $order->getSubtotal() - $order->getDiscountAmount() + $order->getShippingAmount() + $order->getTaxAmount(),
            2,
        );
        expect(round((float) $order->getGrandTotal(), 2))->toBe($expected);
    });

    it('gives a shipment a tracking number and a carrier', function () {
        $shipment = $this->variables['shipment'];
        $tracks = $shipment->getAllTracks();

        expect($shipment->getAllItems())->not->toBeEmpty();
        expect($tracks)->toHaveCount(1);
        expect($tracks[0]->getNumber())->not->toBe('');
        expect($tracks[0]->getTitle())->not->toBe('');
    });

    it('renders every shipped template without an error and without a directive left', function () {
        $empty = [];
        $broken = [];

        foreach (array_keys(Mage_Core_Model_Email_Template::getDefaultTemplates()) as $code) {
            try {
                $template = Mage::getModel('core/email_template');
                $template->loadDefault($code, 'en_US');
                if (!$template->getTemplateText()) {
                    continue;
                }
                $html = (string) $template->getProcessedTemplate($this->variables);
                if ($html === '') {
                    $empty[] = $code;
                }
                if (str_contains($html, '{{')) {
                    $broken[] = $code;
                }
            } catch (Throwable $e) {
                $broken[] = $code . ': ' . $e->getMessage();
            }
        }

        expect($empty)->toBe([]);
        expect($broken)->toBe([]);
    });

    it('renders the customer as a name where the template prints it as a string', function () {
        $template = Mage::getModel('core/email_template');
        $template->loadDefault('checkout_payment_failed_template', 'en_US');
        $html = (string) $template->getProcessedTemplate($this->variables);

        expect($html)->toContain($this->variables['customer']->getName());
    });
});
