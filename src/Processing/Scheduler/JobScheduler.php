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

use DateTime;

class JobScheduler implements SchedulerInterface
{
    const NAME = 'job';

    public function __construct(private readonly DateTime $scheduledAt, private readonly DateTime $modifiedAt)
    {
    }

    public function isExecutable(?DateTime $executedAt): bool
    {
        $now = new DateTime();

        $hasExecutedInPast = $executedAt && $this->scheduledAt <= $executedAt;
        $isTimeToExecute = $now >= $this->scheduledAt;
        $isModifiedBeforeSchedule = $this->modifiedAt <= $this->scheduledAt;

        if ($isTimeToExecute && $isModifiedBeforeSchedule && !$hasExecutedInPast) {
            return true;
        }

        return false;
    }
}
