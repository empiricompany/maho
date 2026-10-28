<?php

/**
 * Random, plausible values for the transactional email preview.
 *
 * The preview renders templates that no store data is guaranteed to back: a fresh installation has
 * no orders, and a store may have no invoices at all. Generating the values keeps the preview
 * useful there, and keeps it from showing the same customer on every shop.
 *
 * The values come from Faker, created with the store locale, because names, addresses and phone
 * numbers that read as local are the point of a preview. A locale Faker does not support falls
 * back to `en_US` instead of breaking the preview.
 *
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Mage_Adminhtml
 */

declare(strict_types=1);

use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;

class Mage_Adminhtml_Model_Email_Template_Preview_Faker
{
    public const FALLBACK_LOCALE = 'en_US';

    /**
     * Carriers are store data rather than fake identity, so they come from this list.
     *
     * @var list<array{code: string, title: string}>
     */
    protected array $_carriers = [
        ['code' => 'ups', 'title' => 'United Parcel Service'],
        ['code' => 'usps', 'title' => 'United States Postal Service'],
        ['code' => 'dhl', 'title' => 'DHL'],
        ['code' => 'fedex', 'title' => 'FedEx'],
    ];

    protected FakerGenerator $_generator;

    public function __construct()
    {
        $locale = (string) Mage::app()->getLocale()->getLocaleCode();

        try {
            $this->_generator = FakerFactory::create($locale);
        } catch (InvalidArgumentException) {
            $this->_generator = FakerFactory::create(self::FALLBACK_LOCALE);
        }
    }

    public function firstName(): string
    {
        return (string) $this->_generator->firstName();
    }

    public function lastName(): string
    {
        return (string) $this->_generator->lastName();
    }

    public function fullName(): string
    {
        return (string) $this->_generator->name();
    }

    public function email(): string
    {
        return (string) $this->_generator->safeEmail();
    }

    /**
     * @return array{city: string, postcode: string, country: string}
     */
    public function place(): array
    {
        return [
            'city' => (string) $this->_generator->city(),
            'postcode' => (string) $this->_generator->postcode(),
            'country' => (string) $this->_generator->countryCode(),
        ];
    }

    public function street(): string
    {
        return (string) $this->_generator->streetAddress();
    }

    public function phone(): string
    {
        return (string) $this->_generator->phoneNumber();
    }

    public function productName(): string
    {
        return ucfirst((string) $this->_generator->words(3, true));
    }

    public function sku(): string
    {
        return strtoupper((string) $this->_generator->bothify('??-####'));
    }

    public function price(): float
    {
        return $this->_generator->randomFloat(2, 3, 150);
    }

    public function quantity(): int
    {
        return $this->_generator->numberBetween(1, 3);
    }

    public function trackingNumber(): string
    {
        return strtoupper((string) $this->_generator->bothify('1Z##########'));
    }

    /**
     * @return array{code: string, title: string}
     */
    public function carrier(): array
    {
        return $this->_carriers[array_rand($this->_carriers)];
    }

    /**
     * A moment in the last month, formatted the way the database stores it.
     */
    public function dateTime(): string
    {
        return $this->_generator->dateTimeBetween('-1 month')->format('Y-m-d H:i:s');
    }

    public function sentence(): string
    {
        return (string) $this->_generator->sentence();
    }
}
