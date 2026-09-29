<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\DataImporterBundle\Log;

use Carbon\Carbon;
use OpenDxp\Bundle\DataImporterBundle\OpenDxpDataImporterBundle;
use OpenDxp\Db;

/**
 * Журнал одного импорта из application_logs — только его компонент `DATA-IMPORTER <импорт>`.
 *
 * Нужен тем, у кого нет права `application_logging`: панель Application Logger отдаёт журнал
 * всего приложения (фильтр по компоненту там только в интерфейсе), а здесь выборка жёстко
 * ограничена одним импортом, доступ проверяет контроллер — как на чтение конфига.
 *
 * «Последний запуск» — записи после последней строки {@see RUN_START_MESSAGE}, которую пишет
 * ImportPreparationService при каждой подготовке импорта. Итог по строке определяется по
 * текстам ImportProcessingService — они в этом же бандле.
 */
class ImportLogService
{
    public const RUN_START_MESSAGE = 'Loading source data from configured source...';

    public const KIND_ALL = 'all';

    public const KIND_IMPORTED = 'imported';

    public const KIND_REJECTED = 'rejected';

    public const KIND_NOT_FOUND = 'notFound';

    private const TABLE = 'application_logs';

    private const ERROR_PRIORITIES = "'error','critical','alert','emergency'";

    private const IMPORTED_LIKE = 'Element % imported successfully.';

    private const NOT_FOUND_LIKE = 'No match by %';

    /** Служебная строка на каждую строку файла — в журнале для людей только шум */
    private const PROCESSING_LIKE = '⭢ Processing DataRow %';

    public function component(string $configName): string
    {
        return OpenDxpDataImporterBundle::LOGGER_COMPONENT_PREFIX . $configName;
    }

    /**
     * @return array{id: int, startedAt: int}|null
     */
    public function lastRun(string $configName): ?array
    {
        $row = Db::get()->fetchAssociative(
            'SELECT id, timestamp FROM ' . self::TABLE . ' WHERE component = ? AND message = ? ORDER BY id DESC LIMIT 1',
            [$this->component($configName), self::RUN_START_MESSAGE]
        );

        return $row ? ['id' => (int) $row['id'], 'startedAt' => $this->toTimestamp($row['timestamp'])] : null;
    }

    /**
     * @return array{startedAt: int, finishedAt: int|null, imported: int, rejected: int, notFound: int, warnings: int}|null
     */
    public function lastRunSummary(string $configName): ?array
    {
        $run = $this->lastRun($configName);
        if ($run === null) {
            return null;
        }

        $row = Db::get()->fetchAssociative(
            'SELECT
                SUM(priority = \'info\' AND message LIKE ?) AS imported,
                SUM(priority IN (' . self::ERROR_PRIORITIES . ')) AS rejected,
                SUM(priority = \'warning\' AND message LIKE ?) AS notFound,
                SUM(priority = \'warning\' AND message NOT LIKE ?) AS warnings,
                MAX(timestamp) AS lastAt
            FROM ' . self::TABLE . '
            WHERE component = ? AND id > ?',
            [self::IMPORTED_LIKE, self::NOT_FOUND_LIKE, self::NOT_FOUND_LIKE, $this->component($configName), $run['id']]
        ) ?: [];

        return [
            'startedAt' => $run['startedAt'],
            'finishedAt' => !empty($row['lastAt']) ? $this->toTimestamp($row['lastAt']) : null,
            'imported' => (int) ($row['imported'] ?? 0),
            'rejected' => (int) ($row['rejected'] ?? 0),
            'notFound' => (int) ($row['notFound'] ?? 0),
            'warnings' => (int) ($row['warnings'] ?? 0),
        ];
    }

    /**
     * @return array{total: int, data: list<array{id: int, timestamp: int, priority: string, message: string, relatedobject: int|null, relatedobjecttype: string|null}>}
     */
    public function entries(string $configName, string $kind, bool $onlyLastRun, int $start, int $limit): array
    {
        $where = ['component = ?'];
        $params = [$this->component($configName)];

        if ($onlyLastRun) {
            $run = $this->lastRun($configName);
            if ($run === null) {
                return ['total' => 0, 'data' => []];
            }
            $where[] = 'id >= ?';
            $params[] = $run['id'];
        }

        switch ($kind) {
            case self::KIND_IMPORTED:
                $where[] = 'priority = \'info\' AND message LIKE ?';
                $params[] = self::IMPORTED_LIKE;
                break;
            case self::KIND_REJECTED:
                $where[] = 'priority IN (' . self::ERROR_PRIORITIES . ')';
                break;
            case self::KIND_NOT_FOUND:
                $where[] = 'priority = \'warning\' AND message LIKE ?';
                $params[] = self::NOT_FOUND_LIKE;
                break;
            default:
                $where[] = 'message NOT LIKE ?';
                $params[] = self::PROCESSING_LIKE;
        }

        $db = Db::get();
        $condition = implode(' AND ', $where);
        $total = (int) $db->fetchOne('SELECT COUNT(*) FROM ' . self::TABLE . ' WHERE ' . $condition, $params);

        $rows = $db->fetchAllAssociative(
            sprintf(
                'SELECT id, timestamp, priority, message, relatedobject, relatedobjecttype FROM %s WHERE %s ORDER BY id DESC LIMIT %d OFFSET %d',
                self::TABLE,
                $condition,
                max(1, min($limit, 500)),
                max(0, $start)
            ),
            $params
        );

        return [
            'total' => $total,
            'data' => array_map(fn (array $row): array => [
                'id' => (int) $row['id'],
                'timestamp' => $this->toTimestamp($row['timestamp']),
                'priority' => (string) $row['priority'],
                'message' => (string) $row['message'],
                'relatedobject' => $row['relatedobject'] !== null ? (int) $row['relatedobject'] : null,
                'relatedobjecttype' => $row['relatedobjecttype'],
            ], $rows),
        ];
    }

    /** application_logs хранит время в UTC — как читает его LogController */
    private function toTimestamp(string $value): int
    {
        return (new Carbon($value, 'UTC'))->getTimestamp();
    }
}
