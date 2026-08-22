<?php
namespace OpenDxp\Bundle\DataImporterBundle\Tests;


use Codeception\Test\Unit;
use OpenDxp\Bundle\DataHubSimpleRestBundle\Tests\ServiceTester;
use OpenDxp\Bundle\DataImporterBundle\Queue\QueueService;
use OpenDxp\Bundle\DataImporterBundle\Processing\ImportProcessingService;

class QueueTest extends Unit
{
    /**
     * @var ServiceTester
     */
    protected $tester;

    public function testQueue()
    {
        /**
         * @var QueueService $queueService
         */
        $queueService = $this->tester->grabService(QueueService::class);

        $queueService->addItemToQueue('tmp', ImportProcessingService::EXECUTION_TYPE_SEQUENTIAL, ImportProcessingService::JOB_TYPE_PROCESS, 'some data');
        $count = $queueService->getQueueItemCount('tmp');
        $this->assertEquals($count, 1, 'Queue Item count check');

        $entryIds = $queueService->getAllQueueEntryIds(ImportProcessingService::EXECUTION_TYPE_SEQUENTIAL,);
        $this->assertCount(1, $entryIds, 'Queue Item count check');

        $entry = $queueService->getQueueEntryById($entryIds[0]);

        $this->assertEquals($entry['data'], 'some data', 'Queue Item id check');
        $this->assertEquals($entry['jobType'], ImportProcessingService::JOB_TYPE_PROCESS, 'Queue Item id check');


        $queueService->markQueueEntryAsProcessed($entryIds[0]);

        $count = $queueService->getQueueItemCount('tmp');
        $this->assertEquals(0, $count, 'Queue Item count re-check');
    }

}
