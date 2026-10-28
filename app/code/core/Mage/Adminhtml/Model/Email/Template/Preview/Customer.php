<?php

/**
 * A customer for the transactional email preview that also reads as its name.
 *
 * Templates disagree on what `{{var customer}}` is: the account emails call methods on it, the
 * payment failed email prints it as the customer name. One object serves both.
 *
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Mage_Adminhtml
 */

declare(strict_types=1);

class Mage_Adminhtml_Model_Email_Template_Preview_Customer extends Mage_Customer_Model_Customer
{
    #[\Override]
    public function __toString(): string
    {
        return (string) $this->getName();
    }
}
