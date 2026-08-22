<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\DataImporterBundle\Queue;

use Carbon\Carbon;
use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\TableNotFoundException;
use OpenDxp\Db;

class QueueService
{
    const QUEUE_TABLE_NAME = 'bundle_data_hub_data_importer_queue';

    /**
     * @return Connection
     */
    protected function getDb()
    {
        /** @var Connection $db */
        $db = Db::get();

        return $db;
    }

    protected function getCurrentQueueTableOperationTime(): int
    {
        $carbonNow = Carbon::now();

        return (int)($carbonNow->getTimestamp() . str_pad((string)$carbonNow->milli, 3, '0'));
    }

    /**
     * @throws Exception
     */
    public function addItemToQueue(string $configName, string $executionType, string $jobType, string $data, int $userOwner = 0): void
    {
        $db = $this->getDb();

        try {
            $db->executeQuery(sprintf(
                'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE timestamp = VALUES(timestamp)',
                self::QUEUE_TABLE_NAME,
                implode(',', ['timestamp', 'configName', 'data', 'executionType', 'jobType', 'userOwner']),
                implode(',', [
                    $this->getCurrentQueueTableOperationTime(),
                    $db->quote($configName),
                    $db->quote($data),
                    $db->quote($executionType),
                    $db->quote($jobType),
                    $userOwner,
                ])
            ));
        } catch (TableNotFoundException) {
            $this->createQueueTableIfNotExisting(function () use ($configName, $executionType, $jobType, $data, $userOwner) {
                $this->addItemToQueue($configName, $executionType, $jobType, $data, $userOwner);
            });
        }
    }

    /**
     * @return mixed|null
     *
     * @throws Exception
     */
    protected function createQueueTableIfNotExisting(?Closure $callable = null)
    {
        $this->getDb()->executeQuery(sprintf('CREATE TABLE IF NOT EXISTS %s (
            id bigint AUTO_INCREMENT,
            timestamp bigint NULL,
            userOwner int unsigned NOT NULL DEFAULT 0,

            configName varchar(80) NULL,
            `data` TEXT null,
            executionType varchar(20) NULL,
            jobType varchar(20) NULL,
            dispatched bigint NULL,
            workerId varchar(13) NULL,
            PRIMARY KEY (id),
            KEY `bundle_index_queue_configName_index` (`configName`),
            KEY `bundle_index_queue_executiontype_workerId` (`executionType`, `workerId`),
            KEY `bundle_index_queue_configName_index_executionType` (`configName`, `executionType`),
            KEY `bundle_index_queue_executiontype_userOwner` (`userOwner`))
        ', self::QUEUE_TABLE_NAME));

        if ($callable) {
            return $callable();
        }

        return null;
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception|Exception
     */
    public function getAllQueueEntryIds(string $executionType, int $limit = 100000, bool $dispatch = false): array
    {
        try {
            if ($dispatch === true) {
                $dispatchId = time();
                $workerId = uniqid();

                $this->getDb()->executeQuery('UPDATE ' . self::QUEUE_TABLE_NAME . ' SET dispatched = ?, workerId = ? WHERE executionType = ? AND (ISNULL(dispatched) OR dispatched < ?) LIMIT ' . intval($limit),
                    [$dispatchId, $workerId, $executionType, $dispatchId - 3000]);

                $results = $this->getDb()->fetchFirstColumn(
                    sprintf('SELECT id FROM %s WHERE executionType = ? AND workerId = ?', self::QUEUE_TABLE_NAME),
                    [$executionType, $workerId]
                );
            } else {
                $results = $this->getDb()->fetchFirstColumn(
                    sprintf('SELECT id FROM %s WHERE executionType = ?', self::QUEUE_TABLE_NAME),
                    [$executionType]
                );
            }

            return $results ?? []; // @phpstan-ignore-line
        } catch (TableNotFoundException) {
            return $this->createQueueTableIfNotExisting(fn () => $this->getAllQueueEntryIds($executionType, $limit));
        }
    }

    /**
     * @throws Exception
     */
    public function getQueueEntryById(int $id): array
    {
        try {
            $result = $this->getDb()->fetchAssociative(
                sprintf('SELECT * FROM %s WHERE id = ?', self::QUEUE_TABLE_NAME),
                [$id]
            );

            return is_array($result) ? $result : [];
        } catch (TableNotFoundException) {
            return $this->createQueueTableIfNotExisting(fn () => $this->getQueueEntryById($id));
        }
    }

    public function getQueueItemCount(string $configName): int
    {
        try {
            return $this->getDb()->fetchOne(
                sprintf('SELECT count(*) as count FROM %s WHERE configName = ?', self::QUEUE_TABLE_NAME),
                [$configName]
            ) ?? 0;
        } catch (TableNotFoundException) {
            return $this->createQueueTableIfNotExisting(fn () => $this->getQueueItemCount($configName));
        }
    }

    /**
     * @param int $id
     *
     * @throws Exception
     */
    public function markQueueEntryAsProcessed($id): void
    {
        try {
            $this->getDb()->executeQuery(
                sprintf('DELETE FROM %s WHERE id = ?', self::QUEUE_TABLE_NAME),
                [$id]
            );
        } catch (TableNotFoundException) {
            $this->createQueueTableIfNotExisting();
        }
    }

    /**
     * @throws Exception
     */
    public function cleanupQueueItems(string $configName): void
    {
        try {
            $this->getDb()->executeQuery(
                sprintf('DELETE FROM %s WHERE configName = ?', self::QUEUE_TABLE_NAME),
                [$configName]
            );
        } catch (TableNotFoundException) {
            $this->createQueueTableIfNotExisting();
        }
    }
}
