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

namespace OpenDxp\Bundle\DataImporterBundle\Processing\Scheduler;

use Cron\CronExpression;
use DateTime;

class CronScheduler implements SchedulerInterface
{
    const NAME = 'cron';

    public function __construct(private readonly string $cronDefinition, private readonly DateTime $modifiedAt)
    {
    }

    public function isExecutable(?DateTime $executedAt): bool
    {
        $cron = new CronExpression($this->cronDefinition);
        $startAt = $executedAt ?: $this->modifiedAt;

        $nextRun = $cron->getNextRunDate($startAt);
        $now = new DateTime();

        return $nextRun < $now;
    }
}
