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

namespace OpenDxp\Bundle\DataImporterBundle\Command;

use OpenDxp\Bundle\DataHubBundle\Configuration\Dao;
use OpenDxp\Bundle\DataImporterBundle\Processing\ImportPreparationService;
use OpenDxp\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronExecutionCommand extends AbstractCommand
{
    public function __construct(
        protected ImportPreparationService $importPreparationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('datahub:data-importer:execute-cron')
            ->setDescription('Executes all data importer configurations corresponding to their cron definition.')
            ->addArgument('config_name', InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Names of configs that should be considered. Uses all if not specified.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configNames = $input->getArgument('config_name');

        if (empty($configNames)) {
            $configNames = [];
            $allDataHubConfiguations = Dao::getList();
            foreach ($allDataHubConfiguations as $dataHubConfig) {
                if (in_array($dataHubConfig->getType(), ['dataImporterDataObject'])) {
                    $configNames[] = $dataHubConfig->getName();
                }
            }
        }

        foreach ($configNames as $configName) {
            $output->writeln("Execution of config '$configName'");
            $this->importPreparationService->execute($configName);
        }

        return 0;
    }
}
