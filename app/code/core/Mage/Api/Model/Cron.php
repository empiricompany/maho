<?php

/**
 * Maho
 *
 * @package    Mage_Api
 * @copyright  Copyright (c) 2023 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Api_Model_Cron
{
    /**
     * Clean session table
     *
     * @param Mage_Cron_Model_Schedule $schedule
     * @return $this
     */
    public function cleanOldSessions($schedule)
    {
        Mage::getResourceSingleton('api/user')->cleanOldSessions(null);
        return $this;
    }
}
