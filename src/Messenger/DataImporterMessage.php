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

namespace OpenDxp\Bundle\DataImporterBundle\Messenger;

class DataImporterMessage
{
    /**
     * Строки очереди помечены `workerId = messageId`. Не promoted-свойство с умолчанием, чтобы
     * сообщения, сериализованные до появления поля, читались как `false` (старое поведение).
     */
    protected bool $ownsRows = false;

    public function __construct(
        protected string $executionType,
        protected array $ids,
        protected string $messageId,
        bool $ownsRows = false
    ) {
        $this->ownsRows = $ownsRows;
    }

    public function ownsRows(): bool
    {
        return $this->ownsRows;
    }

    public function getExecutionType(): string
    {
        return $this->executionType;
    }

    public function getIds(): array
    {
        return $this->ids;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }
}
