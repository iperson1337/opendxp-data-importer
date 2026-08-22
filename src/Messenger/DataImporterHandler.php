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
        protected $workerCountParallel
    ) {
        $this->workerCounts[ImportProcessingService::EXECUTION_TYPE_PARALLEL] = $this->workerCountParallel;
    }

    public function __invoke(DataImporterMessage $message)
    {
        foreach ($message->getIds() as $id) {
            $this->importProcessingService->processQueueItem($id);
        }

        $this->removeMessage($message->getMessageId());
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

                $this->addMessage($messageId, $executionType);
                $this->messageBus->dispatch(new DataImporterMessage($executionType, $ids, $messageId));
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
