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

use OpenDxp\Console\AbstractCommand;
use OpenDxp\Console\Traits\Parallelization;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ParallelizationAbstractCommand extends AbstractCommand
{
    use Parallelization
    {
        Parallelization::runBeforeFirstCommand as parentRunBeforeFirstCommand;
        Parallelization::runAfterBatch as parentRunAfterBatch;
    }

    protected function configure()
    {
        self::configureCommand($this);
    }

    abstract protected function doFetchItems(InputInterface $input, ?OutputInterface $output): array;

    protected function fetchItems(InputInterface $input, OutputInterface $output): array
    {
        return $this->doFetchItems($input, $output);
    }
}
