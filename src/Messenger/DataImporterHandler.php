<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace OpenDxp\Bundle\DataImporterBundle\Messenger;

use OpenDxp\Bundle\DataImporterBundle\Processing\ImportProcessingService;
use OpenDxp\Bundle\DataImporterBundle\Queue\QueueService;
use OpenDxp\Model\Tool\TmpStore;
use Symfony\Component\Messenger\MessageBusInterface;

class DataImporterHandler
{
    const IMPORTER_WORKER_COUNT_TMP_STORE_KEY_PREFIX = 'DATA-IMPORTER::worker-count::';

    protected array $workerCounts = [
        ImportProcessingService::EXECUTION_TYPE_PARALLEL => 3,
        ImportProcessingService::EXECUTION_TYPE_SEQUENTIAL => 1,
    ];

    /**
     * @param QueueService $queueService
     * @param ImportProcessingService $importProcessingService
     * @param MessageBusInterface $messageBus
     * @param int $workerCountLifeTime
     * @param int $workerItemCount
     * @param int $workerCountParallel
     */
    public function __construct(
        protected QueueService $queueService,
        protected ImportProcessingService $importProcessingService,
        protected MessageBusInterface $messageBus,
        protected int $workerCountLifeTime,
        protected int $workerItemCount,
        protected $workerCountParallel
    ) {
        $this->workerCounts[ImportProcessingService::EXECUTION_TYPE_PARALLEL] = $this->workerCountParallel;
    }

    public function __invoke(DataImporterMessage $message)
    {
        try {
            foreach ($message->getIds() as $id) {
                $this->importProcessingService->processQueueItem($id);
            }
        } finally {
            // Always release the worker-count marker, even if a queue item throws something
            // processQueueItem() didn't catch — otherwise dispatchMessages() stays convinced
            // a worker is still running until the marker's lifetime (default 30 min) expires.
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
                // here, a dispatch that never happened leaves no orphaned marker behind — the
                // previous order left a multi-minute window where a killed process (e.g. a
                // container restart) could set the marker, get killed before/while sending the
                // message, and leave dispatchMessages() blocked for the marker's full lifetime.
                $this->messageBus->dispatch(new DataImporterMessage($executionType, $ids, $messageId));
                $this->addMessage($messageId, $executionType);
                $dispatchedMessageCount = $this->getMessageCount($executionType);
            } else {
                $addWorkers = false;
            }
        }
    }

    private function addMessage(string $messageId, string $executionType)
    {
        TmpStore::set(self::IMPORTER_WORKER_COUNT_TMP_STORE_KEY_PREFIX . $messageId, true, self::IMPORTER_WORKER_COUNT_TMP_STORE_KEY_PREFIX . $executionType, $this->workerCountLifeTime);
    }

    private function removeMessage(string $messageId)
    {
        TmpStore::delete(self::IMPORTER_WORKER_COUNT_TMP_STORE_KEY_PREFIX . $messageId);
    }

    private function getMessageCount(string $executionType): int
    {
        $ids = TmpStore::getIdsByTag(self::IMPORTER_WORKER_COUNT_TMP_STORE_KEY_PREFIX . $executionType);
        $runningWorkers = [];
        foreach ($ids as $id) {
            $runningWorkers[] = TmpStore::get($id);
        }

        return count(array_filter($runningWorkers));
    }
}
