<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\DataImporterBundle\Processing;

use OpenDxp\Bundle\DataHubBundle\Execution\RunningExecution;
use OpenDxp\Bundle\DataHubBundle\Execution\RunningExecutionProviderInterface;
use OpenDxp\Bundle\DataImporterBundle\Queue\QueueService;
use OpenDxp\Model\Tool\TmpStore;

/**
 * Импорты в индикаторе «Идёт выполнение» Data Hub: конфиг выполняется, пока у него есть строки
 * в очереди. Прогресс считается так же, как {@see ImportProcessingService::getImportStatus()},
 * но очередь читается одним GROUP BY на все конфиги, а не COUNT на каждый.
 */
final class RunningImportProvider implements RunningExecutionProviderInterface
{
    public function __construct(private readonly QueueService $queueService)
    {
    }

    public function getRunningExecutions(): iterable
    {
        foreach ($this->queueService->getQueueItemCountsByConfig() as $configName => $queued) {
            // Ключ массива из чисел PHP превращает в int.
            $configName = (string) $configName;
            $total = TmpStore::get(ImportProcessingService::INFO_ENTRY_ID_PREFIX . $configName)?->getData();

            // Без записи об общем объёме (подготовка ещё идёт) — как в getImportStatus(): 0 из очереди.
            if (!is_numeric($total)) {
                yield new RunningExecution($configName, 0, $queued);

                continue;
            }

            $total = (int) $total;
            yield new RunningExecution($configName, max(0, $total - $queued), $total);
        }
    }
}
