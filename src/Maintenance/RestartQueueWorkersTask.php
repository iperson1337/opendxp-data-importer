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
