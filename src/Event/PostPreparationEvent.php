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

namespace OpenDxp\Bundle\DataImporterBundle\Event;

class PostPreparationEvent
{
    public function __construct(
        protected string $configName,
        protected string $executionType,
        protected bool $fileInterpreted
    ) {
    }

    public function getConfigName(): string
    {
        return $this->configName;
    }

    public function getExecutionType(): string
    {
        return $this->executionType;
    }

    public function isFileInterpreted(): bool
    {
        return $this->fileInterpreted;
    }
}
