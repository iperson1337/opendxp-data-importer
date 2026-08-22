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

namespace OpenDxp\Bundle\DataImporterBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use OpenDxp\Bundle\DataImporterBundle\Queue\QueueService;
use OpenDxp\Migrations\BundleAwareMigration;

class Version20220304130000 extends BundleAwareMigration
{
    public function up(Schema $schema): void
    {
        // Idempotent on purpose: on databases migrated from Pimcore the schema was
        // already brought to this state under the old migration identity
        // (Pimcore\Bundle\DataImporterBundle\Migrations\Version20220304130000),
        // which Doctrine does not recognise after the namespace rename and tries
        // to run this version again.
        if (!$schema->hasTable(QueueService::QUEUE_TABLE_NAME)) {
            return;
        }

        $queueTable = $schema->getTable(QueueService::QUEUE_TABLE_NAME);

        if (!$queueTable->hasColumn('dispatched')) {
            $queueTable->addColumn('dispatched', 'bigint', ['notnull' => false, 'default' => null]);
        }

        if (!$queueTable->hasColumn('workerId')) {
            $queueTable->addColumn('workerId', 'string', ['notnull' => false, 'default' => null, 'length' => 13]);
        }

        if (!$queueTable->hasIndex('bundle_index_queue_executiontype_workerId')) {
            $queueTable->addIndex(['executionType', 'workerId'], 'bundle_index_queue_executiontype_workerId');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(QueueService::QUEUE_TABLE_NAME)) {
            return;
        }

        $queueTable = $schema->getTable(QueueService::QUEUE_TABLE_NAME);

        if ($queueTable->hasIndex('bundle_index_queue_executiontype_workerId')) {
            $queueTable->dropIndex('bundle_index_queue_executiontype_workerId');
        }

        if ($queueTable->hasColumn('dispatched')) {
            $queueTable->dropColumn('dispatched');
        }

        if ($queueTable->hasColumn('workerId')) {
            $queueTable->dropColumn('workerId');
        }
    }

    protected function getBundleName(): string
    {
        return 'OpenDxpDataImporterBundle';
    }
}
