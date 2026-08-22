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

use OpenDxp\Bundle\DataImporterBundle\Processing\ImportPreparationService;
use OpenDxp\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PrepareImportCommand extends AbstractCommand
{
    /**
     * @var ImportPreparationService
     */
    protected $importPreparationService;

    /**
     * PrepareImportCommand constructor.
     */
    public function __construct(ImportPreparationService $importPreparationService)
    {
        parent::__construct();
        $this->importPreparationService = $importPreparationService;
    }

    protected function configure()
    {
        $this
            ->setName('datahub:data-importer:prepare-import')
            ->setDescription('Loads and interprets data source file and prepares queue items for import.')
            ->addArgument('config_name', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Names of configs that should be considered.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configNames = $input->getArgument('config_name');

        if (empty($configNames)) {
            $output->writeln('No config given, nothing to do.');
        } else {
            foreach ($configNames as $configName) {
                $output->writeln("Preparing import for config '$configName'");
                $this->importPreparationService->prepareImport($configName);
            }
        }

        return 0;
    }
}
