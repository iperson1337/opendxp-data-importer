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

namespace OpenDxp\Bundle\DataImporterBundle\Mapping;

use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Bundle\DataImporterBundle\Mapping\DataTarget\DataTargetInterface;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\OperatorInterface;

class MappingConfigurationFactory
{
    /**
     * @var MappingConfiguration
     */
    protected $mappingConfigurationBluePrint;

    /**
     * @var OperatorInterface[]
     */
    protected $operatorBluePrints;

    /**
     * @var DataTargetInterface[]
     */
    protected $dataTargetBluePrints;

    /**
     * @param OperatorInterface[] $operatorBluePrints
     * @param DataTargetInterface[] $dataTargetBluePrints
     */
    public function __construct(MappingConfiguration $mappingConfigurationBluePrint, array $operatorBluePrints, array $dataTargetBluePrints)
    {
        $this->mappingConfigurationBluePrint = $mappingConfigurationBluePrint;
        $this->operatorBluePrints = $operatorBluePrints;
        $this->dataTargetBluePrints = $dataTargetBluePrints;
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function buildTransformationPipeline(string $configName, array $configArray): array
    {
        $transformationPipeline = [];

        foreach ($configArray as $config) {
            if (empty($config['type']) || !array_key_exists($config['type'], $this->operatorBluePrints)) {
                throw new InvalidConfigurationException('Unknown operator type `' . ($config['type'] ?? '') . '`');
            }

            $operator = clone $this->operatorBluePrints[$config['type']];
            $operator->setSettings(($config['settings'] ?? []));
            $operator->setConfigName($configName);

            $transformationPipeline[] = $operator;
        }

        return $transformationPipeline;
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function buildDataTarget(array $config): DataTargetInterface
    {
        if (empty($config['type']) || !array_key_exists($config['type'], $this->dataTargetBluePrints)) {
            throw new InvalidConfigurationException('Unknown data target type `' . ($config['type'] ?? '') . '`');
        }

        $dataTarget = clone $this->dataTargetBluePrints[$config['type']];
        $dataTarget->setSettings($config['settings'] ?? []);

        return $dataTarget;
    }

    /**
     * @return MappingConfiguration[]
     *
     * @throws InvalidConfigurationException
     */
    public function loadMappingConfiguration(string $configName, array $configurationArray, bool $ignoreDataTarget = false): array
    {
        $mappingConfigurationCollection = [];

        foreach ($configurationArray as $configurationEntry) {
            $mappingConfigurationCollection[] = $this->loadMappingConfigurationItem($configName, $configurationEntry, $ignoreDataTarget);
        }

        return $mappingConfigurationCollection;
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function loadMappingConfigurationItem(string $configName, array $configurationEntry, bool $ignoreDataTarget = false): MappingConfiguration
    {
        $mappingConfiguration = clone $this->mappingConfigurationBluePrint;

        $mappingConfiguration->setLabel($configurationEntry['label'] ?? '');
        $mappingConfiguration->setDataSourceIndex($configurationEntry['dataSourceIndex'] ?? null);
        $mappingConfiguration->setTransformationPipeline($this->buildTransformationPipeline($configName, $configurationEntry['transformationPipeline'] ?? []));
        if (!$ignoreDataTarget) {
            $mappingConfiguration->setDataTarget($this->buildDataTarget($configurationEntry['dataTarget'] ?? []));
        }

        return $mappingConfiguration;
    }
}
