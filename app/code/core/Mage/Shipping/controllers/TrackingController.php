<?php

/**
 * Maho
 *
 * @package    Mage_Shipping
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sales orders controller
 *
 * @package    Mage_Sales
 */
class Mage_Shipping_TrackingController extends Mage_Core_Controller_Front_Action
{
    public function ajaxAction()
    {
        if ($order = $this->_initOrder()) {
            $response = '';
            $tracks = $order->getTracksCollection();

            $className = Mage::getConfig()->getBlockClassName('core/template');
            /** @var Mage_Core_Block_Template $block */
            $block = new $className();
            $block->setType('core/template')
                ->setIsAnonymous(true)
                ->setTemplate('sales/order/trackinginfo.phtml');

            foreach ($tracks as $track) {
                $trackingInfo = $track->getNumberDetail();
                $block->setTrackingInfo($trackingInfo);
                $response .= $block->toHtml() . "\n<br />";
            }

            $this->getResponse()->setBody($response);
        }
    }

    /**
     * Popup action
     * Shows tracking info if it's present, otherwise redirects to 404
     */
    public function popupAction()
    {
        $shippingInfoModel = Mage::getModel('shipping/info')->loadByHash($this->getRequest()->getParam('hash'));
        Mage::register('current_shipping_info', $shippingInfoModel);
        if (count($shippingInfoModel->getTrackingInfo()) == 0) {
            $this->norouteAction();
            return;
        }
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Initialize order model instance
     *
     * @return Mage_Sales_Model_Order|false
     */
    protected function _initOrder()
    {
        $id = $this->getRequest()->getParam('order_id');

        $order = Mage::getModel('sales/order')->load($id);
        $customerId = Mage::getSingleton('customer/session')->getCustomerId();

        if (!$order->getId() || !$customerId || $order->getCustomerId() != $customerId) {
            return false;
        }
        return $order;
    }
}
