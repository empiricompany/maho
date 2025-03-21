<?php

/**
 * Maho
 *
 * @package    Mage_GiftMessage
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @package    Mage_GiftMessage
 * @deprecated after 1.3.2.4
 */
class Mage_GiftMessage_IndexController extends Mage_Core_Controller_Front_Action
{
    public function saveAction()
    {
        $giftMessage = Mage::getModel('giftmessage/message');
        if ($this->getRequest()->getParam('message')) {
            $giftMessage->load($this->getRequest()->getParam('message'));
        }
        try {
            $entity = $giftMessage->getEntityModelByType($this->_getMappedType($this->getRequest()->getParam('type')));

            $giftMessage->setSender($this->getRequest()->getParam('sender'))
                ->setRecipient($this->getRequest()->getParam('recipient'))
                ->setMessage($this->getRequest()->getParam('messagetext'))
                ->save();

            $entity->load($this->getRequest()->getParam('item'))
                ->setGiftMessageId($giftMessage->getId())
                ->save();

            $this->getRequest()->setParam('message', $giftMessage->getId());
            $this->getRequest()->setParam('entity', $entity);
        } catch (Exception $e) {
        }

        $this->loadLayout();
        $this->renderLayout();
    }
}
