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

namespace OpenDxp\Bundle\DataImporterBundle\Messenger;

use OpenDxp\Bundle\DataImporterBundle\Processing\ImportProcessingService;
use OpenDxp\Bundle\DataImporterBundle\Queue\QueueService;
use OpenDxp\Model\Tool\TmpStore;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Раздаёт строки очереди импорта воркерам Messenger пачками и следит, сколько пачек в работе.
 *
 * Пачка в работе — метка в TmpStore (тег по типу выполнения). Для последовательного импорта
 * разрешена одна пачка, для параллельного — worker_count_parallel.
 *
 * Метка хранит id строк пачки и «пульс» — время, когда воркер последний раз взялся за строку.
 * Метка считается мёртвой и не держит очередь, если:
 * - в очереди не осталось ни одной её строки (обработаны, отменены «Отменить выполнение»,
 *   или воркер закончил раньше, чем метку успели поставить);
 * - пульс старше worker_heartbeat_timeout — воркер убит посреди пачки (деплой, OOM,
 *   перезапуск). Необработанные строки такой пачки сразу возвращаются в очередь.
 * Живой воркер обновляет пульс перед каждой строкой, поэтому во время деплоя, когда старый под
 * ещё дорабатывает пачку, а новый уже слушает очередь, его пачку никто не перехватывает.
 * Раньше метка жила до worker_count_lifetime (30 мин) и после убитого воркера молча
 * блокировала все запуски, а строки его пачки ждали ещё 50 минут (QueueService::getAllQueueEntryIds).
 */
class DataImporterHandler
{
    const IMPORTER_WORKER_COUNT_TMP_STORE_KEY_PREFIX = 'DATA-IMPORTER::worker-count::';

    protected array $workerCounts = [
        ImportProcessingService::EXECUTION_TYPE_PARALLEL => 3,
        ImportProcessingService::EXECUTION_TYPE_SEQUENTIAL => 1,
    ];

    /**
     * @param int $workerCountParallel
     */
    public function __construct(
        protected QueueService $queueService,
        protected ImportProcessingService $importProcessingService,
        protected MessageBusInterface $messageBus,
        protected int $workerCountLifeTime,
        protected int $workerItemCount,
        protected $workerCountParallel,
        protected int $workerHeartbeatTimeout = 300
    ) {
        $this->workerCounts[ImportProcessingService::EXECUTION_TYPE_PARALLEL] = $this->workerCountParallel;
    }

    public function __invoke(DataImporterMessage $message)
    {
        try {
            foreach ($message->getIds() as $id) {
                $this->heartbeat($message);
                $this->importProcessingService->processQueueItem($id);
            }
        } finally {
            // Always release the worker-count marker, even if a queue item throws something
            // processQueueItem() didn't catch.
            $this->removeMessage($message->getMessageId());
        }

        $this->dispatchMessages($message->getExecutionType());
    }

    public function dispatchMessages(string $executionType)
    {
        $dispatchedMessageCount = $this->getMessageCount($executionType);

        $addWorkers = true;
        while ($addWorkers && $dispatchedMessageCount < ($this->workerCounts[$executionType] ?? 1)) {
            $ids = $this->queueService->getAllQueueEntryIds($executionType, $this->workerItemCount, true);
            if (!empty($ids)) {
                $messageId = uniqid();

                // Dispatch before marking the worker as running: if the process dies right
                // here, a dispatch that never happened leaves no orphaned marker behind.
                $this->messageBus->dispatch(new DataImporterMessage($executionType, $ids, $messageId));
                $this->addMessage($messageId, $executionType, $ids);
                $dispatchedMessageCount = $this->getMessageCount($executionType);
            } else {
                $addWorkers = false;
            }
        }
    }

    private function markerId(string $messageId): string
    {
        return self::IMPORTER_WORKER_COUNT_TMP_STORE_KEY_PREFIX . $messageId;
    }

    private function markerTag(string $executionType): string
    {
        return self::IMPORTER_WORKER_COUNT_TMP_STORE_KEY_PREFIX . $executionType;
    }

    private function addMessage(string $messageId, string $executionType, array $ids): void
    {
        // Воркер мог уже взяться за пачку и поставить пульс — не затираем его
        $existing = TmpStore::get($this->markerId($messageId));
        $heartbeat = is_array($existing?->getData()) ? ($existing->getData()['heartbeat'] ?? null) : null;

        TmpStore::set(
            $this->markerId($messageId),
            ['ids' => array_values(array_map('intval', $ids)), 'heartbeat' => $heartbeat],
            $this->markerTag($executionType),
            $this->workerCountLifeTime
        );
    }

    private function heartbeat(DataImporterMessage $message): void
    {
        TmpStore::set(
            $this->markerId($message->getMessageId()),
            ['ids' => array_values(array_map('intval', $message->getIds())), 'heartbeat' => time()],
            $this->markerTag($message->getExecutionType()),
            $this->workerCountLifeTime
        );
    }

    private function removeMessage(string $messageId): void
    {
        TmpStore::delete($this->markerId($messageId));
    }

    private function getMessageCount(string $executionType): int
    {
        $count = 0;
        foreach (TmpStore::getIdsByTag($this->markerTag($executionType)) as $markerId) {
            $marker = TmpStore::get($markerId);
            if (!$marker) {
                continue;
            }

            $data = $marker->getData();
            // Метка старого формата (true) — без id строк проверить нечего, считаем как раньше
            if (!is_array($data)) {
                $count++;
                continue;
            }

            $remaining = $this->queueService->filterExistingIds($data['ids'] ?? []);
            if ($remaining === []) {
                TmpStore::delete($markerId);
                continue;
            }

            $heartbeat = $data['heartbeat'] ?? null;
            if ($heartbeat !== null && $heartbeat < time() - $this->workerHeartbeatTimeout) {
                // Воркер убит посреди пачки — вернуть её строки в очередь и не ждать
                $this->queueService->resetDispatched($remaining);
                TmpStore::delete($markerId);
                continue;
            }

            $count++;
        }

        return $count;
    }
}
