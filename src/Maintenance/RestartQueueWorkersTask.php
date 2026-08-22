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

namespace OpenDxp\Bundle\DataImporterBundle\Maintenance;

use OpenDxp\Bundle\DataImporterBundle\Messenger\DataImporterHandler;
use OpenDxp\Bundle\DataImporterBundle\Processing\ImportProcessingService;
use OpenDxp\Maintenance\TaskInterface;

class RestartQueueWorkersTask implements TaskInterface
{
    public function __construct(
        protected DataImporterHandler $dataImporterHandler,
        protected bool $messengerQueueActivated
    ) {
    }

    public function execute(): void
    {
        if ($this->messengerQueueActivated === true) {
            $this->dataImporterHandler->dispatchMessages(ImportProcessingService::EXECUTION_TYPE_SEQUENTIAL);
            $this->dataImporterHandler->dispatchMessages(ImportProcessingService::EXECUTION_TYPE_PARALLEL);
        }
    }
}
