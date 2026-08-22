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

use OpenDxp;
use OpenDxp\Bundle\DataImporterBundle\Processing\ImportProcessingService;
use OpenDxp\Bundle\DataImporterBundle\Queue\QueueService;
use OpenDxp\Console\AbstractCommand;
use OpenDxp\Console\Style\OpenDxpStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Throwable;

class SequentialProcessQueueCommand extends AbstractCommand
{
    /**
     * @var ImportProcessingService
     */
    protected $importProcessingService;

    /**
     * @var QueueService
     */
    protected $queueService;

    /**
     * @var LockInterface|null
     */
    private $lock;

    public function __construct(ImportProcessingService $importProcessingService, QueueService $queueService)
    {
        parent::__construct();
        $this->importProcessingService = $importProcessingService;
        $this->queueService = $queueService;
    }

    public function configure(): void
    {
        $this
            ->setName('datahub:data-importer:process-queue-sequential')
            ->setDescription('Processes all items of the queue that need to be executed sequential.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $this->writeError('The command is already running.');

            return Command::FAILURE;
        }

        $io = new OpenDxpStyle($input, $output);

        try {
            $itemIds = $this->queueService->getAllQueueEntryIds(ImportProcessingService::EXECUTION_TYPE_SEQUENTIAL);
            $itemCount = count($itemIds);

            $output->writeln("Processing {$itemCount} items sequentially\n");

            $progressBar = new ProgressBar($output, $itemCount);
            $progressBar->start();

            foreach ($itemIds as $i => $id) {
                $this->importProcessingService->processQueueItem($id);
                $progressBar->advance();

                // call the garbage collector to avoid too many connections & memory issue
                if (($i + 1) % 200 === 0) {
                    OpenDxp::collectGarbage();
                }
            }

            $progressBar->finish();

            $this->release(); //release the lock

            $output->writeln("\n\nProcessed {$itemCount} items.");

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->release();
            $io->error($e->getMessage());
        }

        return Command::FAILURE;
    }

    /**
     * Locks the command.
     */
    private function lock(): bool
    {
        $this->lock = OpenDxp::getContainer()->get(LockFactory::class)->createLock($this->getName(), 86400);

        if (!$this->lock->acquire(false)) {
            $this->lock = null;

            return false;
        }

        return true;
    }

    /**
     * Releases the command lock if there is one.
     */
    private function release()
    {
        if ($this->lock) {
            $this->lock->release();
            $this->lock = null;
        }
    }
}
