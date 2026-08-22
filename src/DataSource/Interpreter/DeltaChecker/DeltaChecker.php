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

namespace OpenDxp\Bundle\DataImporterBundle\DataSource\Interpreter\DeltaChecker;

use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\TableNotFoundException;

class DeltaChecker
{
    /**
     * @var Connection
     */
    protected $db;

    const CACHE_TABLE_NAME = 'bundle_data_hub_data_importer_delta_cache';

    public function __construct(Connection $connection)
    {
        $this->db = $connection;
    }

    /**
     * @return mixed|null
     *
     * @throws Exception
     */
    protected function createTableIfNotExisting(?Closure $callable = null)
    {
        $this->db->executeQuery(sprintf('CREATE TABLE IF NOT EXISTS %s (
            configName varchar(80) NOT NULL,
            id varchar(50) NOT NULL,
            hash varchar(100) NOT NULL,
            PRIMARY KEY (configName, id))
        ', self::CACHE_TABLE_NAME));

        if ($callable) {
            return $callable();
        }

        return null;
    }

    protected function getCurrentHash(string $configName, string $id): string
    {
        try {
            return $this->db->fetchOne(
                sprintf('SELECT hash FROM %s WHERE configName = ? AND id = ?', self::CACHE_TABLE_NAME),
                [$configName, $id]
            ) ?? '';
        } catch (TableNotFoundException) {
            return $this->createTableIfNotExisting(fn () => $this->getCurrentHash($configName, $id));
        }
    }

    protected function updateHash(string $configName, string $id, string $hash)
    {
        try {
            $this->db->executeQuery(
                sprintf('INSERT INTO %s (configName, id, hash) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE hash = ?', self::CACHE_TABLE_NAME),
                [$configName, $id, $hash, $hash]
            );
        } catch (TableNotFoundException) {
            $this->createTableIfNotExisting(function () use ($configName, $id, $hash) {
                $this->updateHash($configName, $id, $hash);
            });
        }
    }

    /**
     * @param mixed $idDataIndex
     */
    public function hasChanged(string $configName, $idDataIndex, array $data): bool
    {
        $id = $data[$idDataIndex] ?? null;
        if ($id === null) {
            return false;
        }

        $oldHash = $this->getCurrentHash($configName, $id);

        $newHash = md5(json_encode($data));
        if (!hash_equals($oldHash, $newHash)) {
            $this->updateHash($configName, $id, $newHash);

            return true;
        } else {
            return false;
        }
    }

    public function cleanup(string $configName)
    {
        try {
            $this->db->executeQuery(
                sprintf('DELETE FROM %s WHERE configName = ?', self::CACHE_TABLE_NAME),
                [$configName]
            );
        } catch (TableNotFoundException) {
            $this->createTableIfNotExisting();
        }
    }
}
