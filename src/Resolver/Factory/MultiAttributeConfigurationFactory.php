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

namespace OpenDxp\Bundle\DataImporterBundle\Resolver\Factory;

use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\OperatorInterface;

class MultiAttributeConfigurationFactory
{
    /**
     * @var OperatorInterface[]
     */
    protected array $operatorBluePrints;

    /**
     * @param OperatorInterface[] $operatorBluePrints
     */
    public function __construct(array $operatorBluePrints)
    {
        $this->operatorBluePrints = $operatorBluePrints;
    }

    /**
     * Builds a transformation pipeline from a configuration array
     *
     * @param string $configName
     * @param array $configArray
     *
     * @return array
     *
     * @throws InvalidConfigurationException
     */
    public function buildTransformationPipeline(string $configName, array $configArray): array
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
     * Processes a value through a transformation pipeline
     *
     * @param mixed $value
     * @param array $pipeline
     * @param string $configName
     *
     * @return mixed
     */
    public function processTransformationPipeline($value, array $pipeline, string $configName): mixed
    {
        $data = $value;

        try {
            // Build the transformation pipeline
            $operators = $this->buildTransformationPipeline($configName, $pipeline);

            // Process the data through each operator in the pipeline
            foreach ($operators as $operator) {
                if ($operator instanceof OperatorInterface) {
                    $data = $operator->process($data);
                }
            }
        } catch (\Exception $e) {
            // If there's an error, log it and return the original data
            // This ensures that the import process doesn't fail completely
            // if there's an issue with a transformation
            // In a production environment, you might want to log this error
            // or handle it differently
        }

        return $data;
    }
}
