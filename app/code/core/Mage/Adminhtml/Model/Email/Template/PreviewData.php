<?php

/**
 * Sample data for the transactional email preview.
 *
 * The preview used to render every template with an empty variable set, so it showed a shell with
 * no customer, no items and no totals. This builds the variables from objects that were never
 * saved, with values generated on each request: a store does not need an order, an invoice or a
 * shipment in its database for a merchant to see what a template looks like, and two shops that
 * install Maho see two different messages instead of the same hardcoded customer.
 *
 * Every template receives the whole set. A template ignores what it does not reference, so one
 * builder covers orders, shipments, invoices, credit memos, customer emails, the contact form, the
 * newsletter and the payment failed email, instead of a map from template code to variables that
 * has to grow with each template the platform adds.
 *
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Mage_Adminhtml
 */

declare(strict_types=1);

class Mage_Adminhtml_Model_Email_Template_PreviewData
{
    protected Mage_Adminhtml_Model_Email_Template_Preview_Faker $_faker;

    public function __construct()
    {
        $this->_faker = Mage::getModel('adminhtml/email_template_preview_faker');
    }

    /**
     * The variables a transactional email template expects, filled with generated data.
     *
     * @param int $storeId
     * @return array<string, mixed>
     */
    public function getVariables($storeId)
    {
        $store = Mage::app()->getStore((int) $storeId);
        $customer = $this->getCustomer();
        $order = $this->getOrder($store, $customer);
        $items = $order->getAllItems();
        $billing = $order->getBillingAddress();

        $variables = [
            'store' => $store,
            'store_name' => $store->getFrontendName(),
            'customer' => $customer,
            'customer_name' => $customer->getName(),
            'customer_email' => $customer->getEmail(),
            'customerEmail' => $customer->getEmail(),
            'order' => $order,
            'billing' => $billing,
            'billingAddress' => $billing,
            'shippingAddress' => $order->getShippingAddress(),
            'payment_html' => $this->getPaymentHtml(),
            'comment' => $this->_faker->sentence(),
            'items' => $this->getItemsHtml($items),
            'total' => Mage::helper('core')->currency($order->getGrandTotal(), true, false),
            'checkoutType' => Mage::helper('core')->__('Onepage Checkout'),
            'reason' => Mage::helper('core')->__('The payment was declined by the bank.'),
            // The administrative templates (log cleanup, currency update, sitemap, product alerts)
            // take the text of the failure as one string.
            'error' => $this->_faker->sentence(),
            'warnings' => $this->_faker->sentence(),
            'message' => $this->_faker->sentence(),
            'shipment' => $this->getShipment($order, $items),
            'invoice' => $this->getInvoice($order, $items),
            'creditmemo' => $this->getCreditmemo($order, $items),
            'subscriber' => $this->getSubscriber($store),
            'data' => $this->getContactForm(),
        ];

        return $variables;
    }

    protected function getCustomer(): Mage_Adminhtml_Model_Email_Template_Preview_Customer
    {
        /** @var Mage_Adminhtml_Model_Email_Template_Preview_Customer $customer */
        $customer = Mage::getModel('adminhtml/email_template_preview_customer');

        return $customer->setData([
            'entity_id' => 1,
            'firstname' => $this->_faker->firstName(),
            'lastname' => $this->_faker->lastName(),
            'email' => $this->_faker->email(),
            'group_id' => 1,
        ]);
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @param Mage_Customer_Model_Customer $customer
     * @return Mage_Adminhtml_Model_Email_Template_Preview_Order
     */
    protected function getOrder(Mage_Core_Model_Store $store, Mage_Customer_Model_Customer $customer)
    {
        /** @var Mage_Adminhtml_Model_Email_Template_Preview_Order $order */
        $order = Mage::getModel('adminhtml/email_template_preview_order');

        $items = $this->getOrderItems($order);
        $totals = $this->getTotals($items);

        $order->setData([
            'entity_id' => 1,
            'increment_id' => $this->getIncrementId(),
            'store_id' => $store->getId(),
            'store_name' => $store->getFrontendName(),
            'created_at' => $this->_faker->dateTime(),
            'customer_id' => $customer->getId(),
            'customer_is_guest' => 0,
            'customer_group_id' => $customer->getGroupId(),
            'customer_firstname' => $customer->getFirstname(),
            'customer_lastname' => $customer->getLastname(),
            'customer_name' => $customer->getName(),
            'customer_email' => $customer->getEmail(),
            'is_virtual' => 0,
            'state' => Mage_Sales_Model_Order::STATE_COMPLETE,
            'status' => 'complete',
            'shipping_description' => Mage::helper('core')->__('Flat Rate - Fixed'),
            'email_customer_note' => $this->_faker->sentence(),
            'total_qty_ordered' => $totals['qty'],
            'total_item_count' => count($items),
            'subtotal' => $totals['subtotal'],
            'subtotal_incl_tax' => $totals['subtotal'],
            'discount_amount' => $totals['discount'],
            'shipping_amount' => $totals['shipping'],
            'shipping_incl_tax' => $totals['shipping'],
            'tax_amount' => $totals['tax'],
            'grand_total' => $totals['grand'],
            'base_currency_code' => $store->getBaseCurrencyCode(),
            'order_currency_code' => $store->getCurrentCurrencyCode(),
            'store_currency_code' => $store->getCurrentCurrencyCode(),
        ]);
        $order->setBillingAddress($this->getAddress($customer, 'billing'));
        $order->setShippingAddress($this->getAddress($customer, 'shipping'));
        $order->setPreviewItems($items);
        $order->setData('items', $items);

        return $order;
    }

    /**
     * @param Mage_Adminhtml_Model_Email_Template_Preview_Order $order
     * @return list<Mage_Sales_Model_Order_Item>
     */
    protected function getOrderItems(Mage_Adminhtml_Model_Email_Template_Preview_Order $order)
    {
        $items = [];
        $count = random_int(1, 3);

        for ($position = 1; $position <= $count; $position++) {
            $price = $this->_faker->price();
            $quantity = $this->_faker->quantity();
            $name = $this->_faker->productName();

            /** @var Mage_Sales_Model_Order_Item $item */
            $item = Mage::getModel('sales/order_item');
            $item->setData([
                'item_id' => $position,
                'order_id' => 1,
                'store_id' => $order->getStoreId(),
                'name' => $name,
                'sku' => $this->_faker->sku(),
                'product_type' => 'simple',
                'qty_ordered' => $quantity,
                'qty_invoiced' => $quantity,
                'qty_shipped' => $quantity,
                'price' => $price,
                'calculation_price' => $price,
                'row_total' => round($price * $quantity, 2),
                'tax_percent' => 22,
                // The order item stores the options as JSON, and the email item renderer decodes
                // them: an array here makes the renderer fail.
                'product_options' => json_encode([
                    'options' => $position === 1 ? [] : [
                        ['label' => Mage::helper('core')->__('Size'), 'value' => 'M', 'print_value' => 'M'],
                    ],
                ]),
            ]);
            $item->setOrder($order);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Totals derived from the items, so the preview never shows a sum that does not add up.
     *
     * @param list<Mage_Sales_Model_Order_Item> $items
     * @return array{qty: int, subtotal: float, discount: float, shipping: float, tax: float, grand: float}
     */
    protected function getTotals(array $items)
    {
        $subtotal = 0.0;
        $qty = 0;
        foreach ($items as $item) {
            $subtotal += (float) $item->getRowTotal();
            $qty += (int) $item->getQtyOrdered();
        }
        $subtotal = round($subtotal, 2);
        $discount = round($subtotal * 0.1, 2);
        $shipping = round(random_int(0, 990) / 100, 2);
        $tax = round(($subtotal - $discount) * 0.22, 2);

        return [
            'qty' => $qty,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'tax' => $tax,
            'grand' => round($subtotal - $discount + $shipping + $tax, 2),
        ];
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param list<Mage_Sales_Model_Order_Item> $items
     * @return Mage_Adminhtml_Model_Email_Template_Preview_Shipment
     */
    protected function getShipment(Mage_Sales_Model_Order $order, array $items)
    {
        $carrier = $this->_faker->carrier();

        /** @var Mage_Adminhtml_Model_Email_Template_Preview_Shipment $shipment */
        $shipment = Mage::getModel('adminhtml/email_template_preview_shipment');
        $shipment->setData([
            'entity_id' => 1,
            'increment_id' => $this->getIncrementId(),
            'order_id' => 1,
            'store_id' => $order->getStoreId(),
            'created_at' => $this->_faker->dateTime(),
            'total_qty' => $order->getTotalQtyOrdered(),
            'shipping_address_id' => 2,
        ]);
        $shipment->setOrder($order);
        $shipment->setPreviewItems($this->getDocumentItems($items, 'sales/order_shipment_item'));
        $shipment->setPreviewTracks([
            Mage::getModel('sales/order_shipment_track')->setData([
                'entity_id' => 1,
                'parent_id' => 1,
                'order_id' => 1,
                'track_number' => $this->_faker->trackingNumber(),
                'title' => $carrier['title'],
                'carrier_code' => $carrier['code'],
            ]),
        ]);

        return $shipment;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param list<Mage_Sales_Model_Order_Item> $items
     * @return Mage_Adminhtml_Model_Email_Template_Preview_Invoice
     */
    protected function getInvoice(Mage_Sales_Model_Order $order, array $items)
    {
        /** @var Mage_Adminhtml_Model_Email_Template_Preview_Invoice $invoice */
        $invoice = Mage::getModel('adminhtml/email_template_preview_invoice');
        $invoice->setData([
            'entity_id' => 1,
            'increment_id' => $this->getIncrementId(),
            'order_id' => 1,
            'store_id' => $order->getStoreId(),
            'created_at' => $this->_faker->dateTime(),
            'state' => Mage_Sales_Model_Order_Invoice::STATE_PAID,
            'subtotal' => $order->getSubtotal(),
            'discount_amount' => $order->getDiscountAmount(),
            'shipping_amount' => $order->getShippingAmount(),
            'tax_amount' => $order->getTaxAmount(),
            'grand_total' => $order->getGrandTotal(),
            'order_currency_code' => $order->getOrderCurrencyCode(),
        ]);
        $invoice->setOrder($order);
        $invoice->setPreviewItems($this->getDocumentItems($items, 'sales/order_invoice_item'));

        return $invoice;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param list<Mage_Sales_Model_Order_Item> $items
     * @return Mage_Adminhtml_Model_Email_Template_Preview_Creditmemo
     */
    protected function getCreditmemo(Mage_Sales_Model_Order $order, array $items)
    {
        /** @var Mage_Adminhtml_Model_Email_Template_Preview_Creditmemo $creditmemo */
        $creditmemo = Mage::getModel('adminhtml/email_template_preview_creditmemo');
        $refunded = $items[0] ?? null;
        $subtotal = $refunded ? (float) $refunded->getRowTotal() : 0.0;
        $tax = round($subtotal * 0.22, 2);

        $creditmemo->setData([
            'entity_id' => 1,
            'increment_id' => $this->getIncrementId(),
            'order_id' => 1,
            'store_id' => $order->getStoreId(),
            'created_at' => $this->_faker->dateTime(),
            'state' => Mage_Sales_Model_Order_Creditmemo::STATE_REFUNDED,
            'subtotal' => $subtotal,
            'shipping_amount' => 0.0,
            'tax_amount' => $tax,
            'grand_total' => round($subtotal + $tax, 2),
            'order_currency_code' => $order->getOrderCurrencyCode(),
            'comment_text' => $this->_faker->sentence(),
        ]);
        $creditmemo->setOrder($order);
        $creditmemo->setPreviewItems($refunded ? $this->getDocumentItems([$refunded], 'sales/order_creditmemo_item') : []);

        return $creditmemo;
    }

    /**
     * Shipment, invoice and credit memo items point at the order item they come from.
     *
     * @param list<Mage_Sales_Model_Order_Item> $items
     * @param string $alias
     * @return list<Mage_Core_Model_Abstract>
     */
    protected function getDocumentItems(array $items, $alias)
    {
        $documents = [];
        foreach ($items as $position => $item) {
            $document = Mage::getModel($alias);
            $document->setData([
                'entity_id' => $position + 1,
                'parent_id' => 1,
                'order_item_id' => $item->getId(),
                'product_id' => $position + 1,
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'price' => $item->getPrice(),
                'qty' => $item->getQtyOrdered(),
                'row_total' => $item->getRowTotal(),
            ]);
            $document->setOrderItem($item);
            $documents[] = $document;
        }

        return $documents;
    }

    /**
     * @param Mage_Customer_Model_Customer $customer
     * @param string $type
     * @return Mage_Sales_Model_Order_Address
     */
    protected function getAddress(Mage_Customer_Model_Customer $customer, $type)
    {
        $place = $this->_faker->place();

        return Mage::getModel('sales/order_address')->setData([
            'entity_id' => $type === 'billing' ? 1 : 2,
            'address_type' => $type,
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
            'street' => [$this->_faker->street()],
            'city' => $place['city'],
            'region' => '',
            'region_id' => 0,
            'postcode' => $place['postcode'],
            'country_id' => $place['country'],
            'telephone' => $this->_faker->phone(),
            'email' => $customer->getEmail(),
        ]);
    }

    /**
     * @param list<Mage_Sales_Model_Order_Item> $items
     * @return string
     */
    protected function getItemsHtml(array $items)
    {
        $rows = [];
        foreach ($items as $item) {
            $rows[] = sprintf(
                '<li>%s &times; %d &mdash; %s</li>',
                Mage::helper('core')->escapeHtml((string) $item->getName()),
                (int) $item->getQtyOrdered(),
                Mage::helper('core')->currency((float) $item->getRowTotal(), true, false),
            );
        }

        return '<ul>' . implode('', $rows) . '</ul>';
    }

    protected function getPaymentHtml(): string
    {
        return sprintf(
            '<p><strong>%s</strong><br>%s</p>',
            Mage::helper('core')->__('Check / Money order'),
            Mage::helper('core')->__('Make payable to the store, quoting the order number.'),
        );
    }

    protected function getSubscriber(Mage_Core_Model_Store $store): Mage_Newsletter_Model_Subscriber
    {
        return Mage::getModel('newsletter/subscriber')->setData([
            'subscriber_id' => 1,
            'store_id' => $store->getId(),
            'subscriber_email' => $this->_faker->email(),
            'subscriber_status' => Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED,
        ]);
    }

    protected function getContactForm(): Varien_Object
    {
        return new Varien_Object([
            'name' => $this->_faker->fullName(),
            'email' => $this->_faker->email(),
            'telephone' => $this->_faker->phone(),
            'comment' => $this->_faker->sentence(),
        ]);
    }

    /**
     * A nine digit number the way the sales entities are numbered.
     */
    protected function getIncrementId(): string
    {
        return sprintf('%09d', random_int(100000000, 999999999));
    }
}
