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

namespace OpenDxp\Bundle\DataImporterBundle\Processing;

use DateTime;
use Exception;
use OpenDxp\Bundle\ApplicationLoggerBundle\ApplicationLogger;
use OpenDxp\Bundle\DataImporterBundle\DataSource\Interpreter\InterpreterFactory;
use OpenDxp\Bundle\DataImporterBundle\DataSource\Loader\DataLoaderFactory;
use OpenDxp\Bundle\DataImporterBundle\Event\PostPreparationEvent;
use OpenDxp\Bundle\DataImporterBundle\Exception\QueueNotEmptyException;
use OpenDxp\Bundle\DataImporterBundle\OpenDxpDataImporterBundle;
use OpenDxp\Bundle\DataImporterBundle\Processing\Scheduler\Exception\InvalidScheduleException;
use OpenDxp\Bundle\DataImporterBundle\Processing\Scheduler\SchedulerFactory;
use OpenDxp\Bundle\DataImporterBundle\Queue\QueueService;
use OpenDxp\Bundle\DataImporterBundle\Resolver\ResolverFactory;
use OpenDxp\Bundle\DataImporterBundle\Settings\ConfigurationPreparationService;
use Psr\Log\LoggerAwareTrait;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ImportPreparationService
{
    const SCHEDULE_TYPE_CRON = 'cron';

    const SCHEDULE_TYPE_JOB = 'job';

    use LoggerAwareTrait;

    /**
     * @var ConfigurationPreparationService
     */
    protected $configLoader;

    /**
     * @var ResolverFactory
     */
    protected $resolverFactory;

    /**
     * @var InterpreterFactory
     */
    protected $interpreterFactory;

    /**
     * @var DataLoaderFactory
     */
    protected $dataLoaderFactory;

    /**
     * @var QueueService
     */
    protected $queueService;

    /**
     * @var ApplicationLogger
     */
    protected $applicationLogger;

    /**
     * @var ExecutionService
     */
    protected $executionService;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * ImportPreparationService constructor.
     */
    public function __construct(
        ResolverFactory $resolverFactory,
        InterpreterFactory $interpreterFactory,
        DataLoaderFactory $dataLoaderFactory,
        QueueService $queueService,
        ApplicationLogger $applicationLogger,
        ConfigurationPreparationService $configurationPreparationService,
        ExecutionService $executionService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->resolverFactory = $resolverFactory;
        $this->interpreterFactory = $interpreterFactory;
        $this->dataLoaderFactory = $dataLoaderFactory;
        $this->queueService = $queueService;
        $this->applicationLogger = $applicationLogger;
        $this->configLoader = $configurationPreparationService;
        $this->executionService = $executionService;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function prepareImport(
        string $configName,
        bool $ignoreActiveFlag = false,
        bool $ignoreNotEmptyQueueFlag = false
    ): bool {
        try {
            $queueItemCount = $this->queueService->getQueueItemCount($configName);
            if ($queueItemCount > 0 && !$ignoreNotEmptyQueueFlag) {
                throw new QueueNotEmptyException("Queue for `$configName` not empty. Not preparing new import, finish queue processing first.");
            }

            $config = $this->configLoader->prepareConfiguration($configName, null, true);

            if (!$ignoreActiveFlag && !$this->isConfigurationActive($configName, $config)) {
                return false;
            }

            $loader = $this->dataLoaderFactory->loadDataLoader($config['loaderConfig']);

            $logMessage = 'Loading source data from configured source...';
            $this->applicationLogger->info($logMessage, [
                'component' => OpenDxpDataImporterBundle::LOGGER_COMPONENT_PREFIX . $configName,
            ]);
            $this->logger->info($logMessage);
            $filePath = $loader->loadData();
            $this->logger->info('Loaded source data from configured source.');

            $resolver = $this->resolverFactory->loadResolver($config['resolverConfig']);
            $interpreter = $this->interpreterFactory->loadInterpreter($configName, $config['interpreterConfig'],
                $config['processingConfig'], $resolver);

            $logMessage = 'Interpreting source file and preparing queue items...';
            $this->logger->info($logMessage);
            $fileInterpreted = $interpreter->interpretFile($filePath);
            $this->logger->info('Interpreted source file and prepared queue items.');

            $this->logger->info('Cleanup source file if necessary.');
            $loader->cleanup();
            $this->logger->info('Cleaned up source file if necessary.');

            $this->eventDispatcher->dispatch(new PostPreparationEvent($configName, $config['processingConfig']['executionType'] ?? ImportProcessingService::EXECUTION_TYPE_SEQUENTIAL, $fileInterpreted));

            return $fileInterpreted;
        } catch (QueueNotEmptyException $e) {
            $message = 'Error preparing Import: ' . $e->getMessage();
            $this->logger->warning($message);

            $this->applicationLogger->warning($message, [
                'component' => OpenDxpDataImporterBundle::LOGGER_COMPONENT_PREFIX . $configName,
            ]);
        } catch (Exception $e) {
            $message = 'Error preparing Import: ';
            $this->logger->warning($message . $e);

            $this->applicationLogger->error($message . $e->getMessage(), [
                'component' => OpenDxpDataImporterBundle::LOGGER_COMPONENT_PREFIX . $configName,
            ]);
        }

        return false;
    }

    public function execute(string $configName)
    {
        $config = $this->configLoader->prepareConfiguration($configName, null, true);

        if (!$this->isConfigurationActive($configName, $config)) {
            return;
        }

        try {
            $scheduler = SchedulerFactory::create($config);
        } catch (InvalidScheduleException $e) {
            $message = "Configuration '$configName' is invalid: {$e->getMessage()}. Skipping job execution.";
            $this->logger->debug($message);

            return;
        }

        $executedAt = $this->executionService->getLastExecution($configName);

        if ($scheduler->isExecutable($executedAt)) {
            $executionDateTime = new DateTime();
            $this->prepareImport($configName);
            $this->executionService->updateExecutionTimestamp($configName, $executionDateTime);
        }
    }

    public function isConfigurationActive(string $configName, array $config): bool
    {
        if (!($config['general']['active'] ?? false)) {
            $message = "Configuration '$configName' is not active, skipping preparation execution.";
            $this->logger->info($message);

            return false;
        }

        return true;
    }
}
