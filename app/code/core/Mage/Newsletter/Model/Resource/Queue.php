<?php

/**
 * Maho
 *
 * @package    Mage_Newsletter
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2023 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Newsletter queue resource model
 *
 * @package    Mage_Newsletter
 */
class Mage_Newsletter_Model_Resource_Queue extends Mage_Core_Model_Resource_Db_Abstract
{
    #[\Override]
    protected function _construct()
    {
        $this->_init('newsletter/queue', 'queue_id');
    }

    /**
     * Add subscribers to queue
     */
    public function addSubscribersToQueue(Mage_Newsletter_Model_Queue $queue, array $subscriberIds)
    {
        if (count($subscriberIds) == 0) {
            Mage::throwException(Mage::helper('newsletter')->__('No subscribers selected.'));
        }

        if (!$queue->getId() && $queue->getQueueStatus() != Mage_Newsletter_Model_Queue::STATUS_NEVER) {
            Mage::throwException(Mage::helper('newsletter')->__('Invalid queue selected.'));
        }

        $adapter = $this->_getWriteAdapter();

        $select = $adapter->select();
        $select->from($this->getTable('newsletter/queue_link'), 'subscriber_id')
            ->where('queue_id = ?', $queue->getId())
            ->where('subscriber_id in (?)', $subscriberIds);

        $usedIds = $adapter->fetchCol($select);
        $adapter->beginTransaction();
        try {
            foreach ($subscriberIds as $subscriberId) {
                if (in_array($subscriberId, $usedIds)) {
                    continue;
                }
                $data = [];
                $data['queue_id'] = $queue->getId();
                $data['subscriber_id'] = $subscriberId;
                $adapter->insert($this->getTable('newsletter/queue_link'), $data);
            }
            $adapter->commit();
        } catch (Exception $e) {
            $adapter->rollBack();
        }
    }

    /**
     * Removes subscriber from queue
     */
    public function removeSubscribersFromQueue(Mage_Newsletter_Model_Queue $queue)
    {
        $adapter = $this->_getWriteAdapter();
        try {
            $adapter->delete(
                $this->getTable('newsletter/queue_link'),
                [
                    'queue_id = ?' => $queue->getId(),
                    'letter_sent_at IS NULL',
                ],
            );

            $adapter->commit();
        } catch (Exception $e) {
            $adapter->rollBack();
        }
    }

    /**
     * Links queue to store
     *
     * @return $this
     */
    public function setStores(Mage_Newsletter_Model_Queue $queue)
    {
        $adapter = $this->_getWriteAdapter();
        $adapter->delete(
            $this->getTable('newsletter/queue_store_link'),
            ['queue_id = ?' => $queue->getId()],
        );

        $stores = $queue->getStores();
        if (!is_array($stores)) {
            $stores = [];
        }

        foreach ($stores as $storeId) {
            $data = [];
            $data['store_id'] = $storeId;
            $data['queue_id'] = $queue->getId();
            $adapter->insert($this->getTable('newsletter/queue_store_link'), $data);
        }
        $this->removeSubscribersFromQueue($queue);

        if (count($stores) == 0) {
            return $this;
        }

        $subscribers = Mage::getResourceSingleton('newsletter/subscriber_collection')
            ->addFieldToFilter('store_id', ['in' => $stores])
            ->useOnlySubscribed()
            ->load();

        $subscriberIds = [];

        /** @var Mage_Newsletter_Model_Subscriber $subscriber */
        foreach ($subscribers as $subscriber) {
            $subscriberIds[] = $subscriber->getId();
        }

        if (count($subscriberIds) > 0) {
            $this->addSubscribersToQueue($queue, $subscriberIds);
        }

        return $this;
    }

    /**
     * Returns queue linked stores
     *
     * @return array
     */
    public function getStores(Mage_Newsletter_Model_Queue $queue)
    {
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()->from($this->getTable('newsletter/queue_store_link'), 'store_id')
            ->where('queue_id = :queue_id');

        if (!($result = $adapter->fetchCol($select, ['queue_id' => $queue->getId()]))) {
            $result = [];
        }

        return $result;
    }

    /**
     * Saving template after saving queue action
     *
     * @param Mage_Newsletter_Model_Queue $queue
     * @return $this
     */
    #[\Override]
    protected function _afterSave(Mage_Core_Model_Abstract $queue)
    {
        if ($queue->getSaveStoresFlag()) {
            $this->setStores($queue);
        }
        return $this;
    }
}
