<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cron\Model\ResourceModel;

/**
 * Schedule resource
 *
 * @api
 * @since 100.0.2
 */
class Schedule extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('cron_schedule', 'schedule_id');
    }

    /**
     * Sets new schedule status only if it's in the expected current status.
     *
     * If schedule is currently in $currentStatus, set it to $newStatus and
     * return true. Otherwise, return false.
     *
     * @param string $scheduleId
     * @param string $newStatus
     * @param string $currentStatus
     * @return bool
     */
    public function trySetJobStatusAtomic($scheduleId, $newStatus, $currentStatus)
    {
        $connection = $this->getConnection();
        $result = $connection->update(
            $this->getTable('cron_schedule'),
            ['status' => $newStatus],
            ['schedule_id = ?' => $scheduleId, 'status = ?' => $currentStatus]
        );
        if ($result == 1) {
            return true;
        }
        return false;
    }

    /**
     * Sets schedule status only if no existing schedules with the same job code
     * have that status.  This is used to implement locking for cron jobs.
     *
     * If the schedule is currently in $currentStatus and there are no existing
     * schedules with the same job code and $newStatus, set the schedule to
     * $newStatus and return true. Otherwise, return false.
     *
     * @param string $scheduleId
     * @param string $newStatus
     * @param string $currentStatus
     * @return bool
     * @since 100.2.0
     */
    public function trySetJobUniqueStatusAtomic($scheduleId, $newStatus, $currentStatus)
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            $this->getTable('cron_schedule'),
            ['job_code']
        )->where(
            'schedule_id  = ?',
            $scheduleId
        );
        $jobCode = $connection->fetchOne($select);
        if (is_null($jobCode)) {
            return false;
        }

        $currentTime = gmdate('c');
        $oneDayAgo = gmdate('c', time() - 60 * 60 * 24);

        // TODO: Would rather use upsert here, but looks like we don't have that in this database abstraction?
        $result = $connection->update(
            $this->getTable('cron_schedule_jobcode_lock'),
            [
                'schedule_id' => $scheduleId,
                // TODO: We should probably be generating the timestamp on the server since we are comparing it on the server
                'executed_at' => $currentTime
            ],
            ["(executed_at < '$oneDayAgo' OR executed_at IS NULL)", 'job_code = ?' => $jobCode]
        );
        if ($result == 1) {
            return true;
        }
        $result = $connection->insert(
            $this->getTable('cron_schedule_jobcode_lock'),
            [
                'schedule_id' => $scheduleId,
                // TODO: We should probably be generating the timestamp on the server since we are comparing it on the server
                'executed_at' => $currentTime,
                'job_code' => $jobCode,
            ]
        );
        if ($result == 1) {
            return true;
        }

        return false;
    }

    /**
     * Deletes the scheduleId from the cron_schedule_jobcode_lock table.
     *
     * @param string $scheduleId
     * @param string $newStatus
     * @param string $currentStatus
     * @return bool
     * @since 100.2.0
     */
    public function deleteScheduleIdFromCronschedulejobcodelock($scheduleId)
    {
        $connection = $this->getConnection();
        $affectedRows = $connection->delete(
            $this->getTable('cron_schedule_jobcode_lock'),
            ['schedule_id = ?' => $scheduleId]
        );
        return 0 < $affectedRows;
    }
}
