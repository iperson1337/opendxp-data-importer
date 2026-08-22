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

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use OpenDxp\Bundle\DataHubBundle\Configuration;
use OpenDxp\Bundle\DataHubBundle\Event\ConfigurationEvents;
use OpenDxp\Bundle\DataImporterBundle\DataSource\Interpreter\DeltaChecker\DeltaChecker;
use OpenDxp\Bundle\DataImporterBundle\Processing\ExecutionService;
use OpenDxp\Bundle\DataImporterBundle\Queue\QueueService;
use OpenDxp\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface as EventSubscriberInterfaceAlias;
use Symfony\Component\EventDispatcher\GenericEvent;

class ConfigurationEventSubscriber implements EventSubscriberInterfaceAlias
{
    /**
     * @var DeltaChecker
     */
    protected $deltaChecker;

    /**
     * @var QueueService
     */
    protected $queueService;

    /**
     * @var ExecutionService
     */
    protected $executionService;

    public function __construct(
        DeltaChecker $deltaChecker,
        QueueService $queueService,
        ExecutionService $executionService,
        protected FilesystemOperator $opendxpDataImporterUploadStorage,
        protected FilesystemOperator $opendxpDataImporterPreviewStorage
    ) {
        $this->deltaChecker = $deltaChecker;
        $this->queueService = $queueService;
        $this->executionService = $executionService;
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigurationEvents::CONFIGURATION_POST_DELETE => 'postDelete',
            ConfigurationEvents::CONFIGURATION_POST_SAVE => 'postSave',
        ];
    }

    public function postDelete(GenericEvent $event)
    {
        /** @var Configuration $config */
        $config = $event->getSubject();

        if ($config->getType() === 'dataImporterDataObject') {
            //cleanup delta cache
            $this->deltaChecker->cleanup($config->getName());

            //cleanup queue
            $this->queueService->cleanupQueueItems($config->getName());

            //cleanup preview files
            try {
                $this->opendxpDataImporterPreviewStorage->deleteDirectory($config->getName());
            } catch (FilesystemException $e) {
                Logger::info($e);
            }

            //cleanup upload files
            try {
                $this->opendxpDataImporterUploadStorage->deleteDirectory($config->getName());
            } catch (FilesystemException $e) {
                Logger::info($e);
            }

            //cleanup cron execution
            $this->executionService->cleanup($config->getName());
        }
    }

    public function postSave(GenericEvent $event)
    {
        /** @var Configuration $config */
        $config = $event->getSubject();

        if ($config->getType() === 'dataImporterDataObject') {
            $this->executionService->initExecution($config->getName());
        }
    }
}
