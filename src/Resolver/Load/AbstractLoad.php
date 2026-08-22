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

use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Bundle\DataImporterBundle\Tool\DataObjectLoader;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\Element\ElementInterface;

abstract class AbstractLoad implements LoadStrategyInterface
{
    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var mixed
     */
    protected $dataSourceIndex;

    /**
     * @var string
     */
    protected $dataObjectClassId;

    /**
     * AbstractLoad constructor.
     */
    public function __construct(Connection $connection, protected DataObjectLoader $dataObjectLoader)
    {
        $this->db = $connection;
    }

    public function setSettings(array $settings): void
    {
        if (!array_key_exists('dataSourceIndex', $settings) || $settings['dataSourceIndex'] === null) {
            throw new InvalidConfigurationException('Empty data source index.');
        }

        $this->dataSourceIndex = $settings['dataSourceIndex'];
    }

    /**
     * @param string $dataObjectClassId
     */
    public function setDataObjectClassId($dataObjectClassId): void
    {
        $this->dataObjectClassId = $dataObjectClassId;
    }

    /**
     * @return string
     *
     * @throws InvalidConfigurationException
     */
    protected function getClassName()
    {
        $class = ClassDefinition::getById($this->dataObjectClassId);
        if (empty($class)) {
            throw new InvalidConfigurationException("Class `{$this->dataObjectClassId}` not found.");
        }

        return '\\OpenDxp\\Model\\DataObject\\' . ucfirst((string) $class->getName());
    }

    /**
     * @throws InvalidArgumentException
     */
    public function loadElement(array $inputData): ?ElementInterface
    {
        return $this->loadElementByIdentifier($this->extractIdentifierFromData($inputData));
    }

    /**
     * @return mixed
     */
    public function extractIdentifierFromData(array $inputData)
    {
        return $inputData[$this->dataSourceIndex] ?? throw new InvalidArgumentException('Identifier not set.');
    }
}
