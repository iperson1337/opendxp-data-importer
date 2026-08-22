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

namespace OpenDxp\Bundle\DataImporterBundle\Resolver\Load;

use OpenDxp\Model\Element\ElementInterface;

class NotLoadStrategy implements LoadStrategyInterface
{
    public function loadElement(array $inputData): ?ElementInterface
    {
        return null;
    }

    /**
     * @param string $identifier
     */
    public function loadElementByIdentifier($identifier): ?ElementInterface
    {
        return null;
    }

    /**
     * @return null
     */
    public function extractIdentifierFromData(array $inputData)
    {
        return null;
    }

    public function loadFullIdentifierList(): array
    {
        return [];
    }

    /**
     * @param string $dataObjectClassId
     */
    public function setDataObjectClassId($dataObjectClassId): void
    {
        //nothing to do
    }

    public function setSettings(array $settings): void
    {
        //nothing to do
    }
}
