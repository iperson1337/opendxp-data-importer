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

use OpenDxp\Bundle\DataImporterBundle\Settings\SettingsAwareInterface;
use OpenDxp\Model\Element\ElementInterface;

interface LoadStrategyInterface extends SettingsAwareInterface
{
    /**
     * Load element based on input data array
     */
    public function loadElement(array $inputData): ?ElementInterface;

    /**
     * Load element based on given identifier (not whole input data array)
     *
     * @param string $identifier
     */
    public function loadElementByIdentifier($identifier): ?ElementInterface;

    /**
     * Extract identifier from input data array
     *
     *
     * @return mixed
     */
    public function extractIdentifierFromData(array $inputData);

    /**
     * Load all in OpenDXP existing identifiers (e.g. all data object IDs of certain data object class)
     */
    public function loadFullIdentifierList(): array;

    /**
     * Set current data object class Id
     *
     * @param string $dataObjectClassId
     */
    public function setDataObjectClassId($dataObjectClassId): void;
}
