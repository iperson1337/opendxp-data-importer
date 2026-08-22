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

namespace OpenDxp\Bundle\DataImporterBundle\EventListener;

use OpenDxp\Bundle\DataImporterBundle\Event\PostPreparationEvent;
use OpenDxp\Bundle\DataImporterBundle\Messenger\DataImporterHandler;

class DataImporterListener
{
    public function __construct(
        protected DataImporterHandler $dataImporterHandler,
        protected bool $messengerQueueActivated
    ) {
    }

    public function importPrepared(PostPreparationEvent $event)
    {
        if (!$this->messengerQueueActivated) {
            return;
        }

        $this->dataImporterHandler->dispatchMessages($event->getExecutionType());
    }
}
