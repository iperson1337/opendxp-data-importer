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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace OpenDxp\Bundle\DataImporterBundle\Resolver\Load;

use Doctrine\DBAL\Connection;
use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Bundle\DataImporterBundle\Resolver\Factory\MultiAttributeConfigurationFactory;
use OpenDxp\Bundle\DataImporterBundle\Tool\DataObjectLoader;
use OpenDxp\Model\DataObject\Listing\Concrete;
use OpenDxp\Model\Element\ElementInterface;

class MultiAttributeLoadStrategy extends AbstractLoad
{
    /**
     * @var array
     */
    protected array $attributeMapping = [];

    /**
     * @var bool
     */
    protected bool $includeUnpublished;

    /**
     * @param Connection $connection
     * @param DataObjectLoader $dataObjectLoader
     * @param MultiAttributeConfigurationFactory $multiAttributeConfigurationFactory
     */
    public function __construct(
        Connection                                   $connection,
        DataObjectLoader                             $dataObjectLoader,
        protected MultiAttributeConfigurationFactory $multiAttributeConfigurationFactory
    ) {
        parent::__construct($connection, $dataObjectLoader);
    }

    /**
     * @param array $settings
     *
     * @throws InvalidConfigurationException
     */
    public function setSettings(array $settings): void
    {
        if (empty($settings['attributeMapping']) || !is_array($settings['attributeMapping'])) {
            throw new InvalidConfigurationException('Empty or invalid attribute mapping.');
        }

        $this->attributeMapping = $settings['attributeMapping'];
        $this->includeUnpublished = $settings['includeUnpublished'] ?? false;

        // Set a stub dataSourceIndex to prevent the parent method from throwing an exception
        $this->dataSourceIndex = 'multi_attribute_strategy';
    }

    /**
     * @param array $inputData
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function extractIdentifierFromData(array $inputData): array
    {
        $identifiers = [];
        foreach ($this->attributeMapping as $attributeConfig) {
            $dataSourceIndex = $attributeConfig['dataSourceIndex'] ?? null;
            if ($dataSourceIndex === null) {
                throw new \InvalidArgumentException('Data source index not set for attribute mapping.');
            }

            if (!isset($inputData[$dataSourceIndex])) {
                throw new \InvalidArgumentException(sprintf('Identifier for data source index "%s" not set.', $dataSourceIndex));
            }

            $value = $inputData[$dataSourceIndex];

            // Apply transformation pipeline if available
            if (!empty($attributeConfig['transformationPipeline']) && is_array($attributeConfig['transformationPipeline'])) {
                $value = $this->applyTransformationPipeline($value, $attributeConfig['transformationPipeline']);
            }

            $identifiers[$attributeConfig['attributeName']] = $value;
        }

        if (empty($identifiers)) {
            throw new \InvalidArgumentException('No identifiers could be extracted from input data.');
        }

        return $identifiers;
    }

    /**
     * Apply transformation pipeline to a value
     *
     * @param mixed $value The value to transform
     * @param array $pipeline The transformation pipeline configuration
     *
     * @return mixed The transformed value
     */
    protected function applyTransformationPipeline($value, array $pipeline): mixed
    {
        // Use the MultiAttributeConfigurationFactory to process the value through the pipeline
        return $this->multiAttributeConfigurationFactory->processTransformationPipeline($value, $pipeline, 'multiAttribute');
    }

    /**
     * @param array $identifiers
     *
     * @return ElementInterface|null
     *
     * @throws InvalidConfigurationException
     */
    public function loadElementByIdentifier($identifiers): ?ElementInterface
    {
        if (!is_array($identifiers)) {
            throw new \InvalidArgumentException('Identifiers must be an array for MultiAttributeLoadStrategy.');
        }

        $className = $this->getClassName();

        /** @var Concrete $list */
        $list = $className::getList();
        $list->setObjectTypes([\OpenDxp\Model\DataObject\AbstractObject::OBJECT_TYPE_OBJECT, \OpenDxp\Model\DataObject\AbstractObject::OBJECT_TYPE_VARIANT]);

        if ($this->includeUnpublished) {
            $list->setUnpublished(true);
        }

        foreach ($identifiers as $attributeName => $value) {
            // MDM-724: то же, что и в DataObjectLoader::loadByAttribute() — выгрузки из
            // ПРОГРЕСС носят nchar-паддинг, а коллация колонок NO PAD, поэтому
            // нетримленный идентификатор мимо существующей записи промахивается.
            if (is_string($value)) {
                $value = trim($value);
            }

            $fieldDefinition = $list->getClass()->getFieldDefinition($attributeName);

            if ($fieldDefinition) {
                $fieldDefinition->addListingFilter($list, $value);
            } else if (is_object($value)) {
                if (method_exists($value, 'getId')) {
                    $list->addConditionParam($attributeName . ' = ?', $value->getId());
                }
            } else {
                $list->addConditionParam($attributeName . ' = ?', $value);
            }
        }

        $list->setLimit(1);
        $objects = $list->load();

        if (!empty($objects)) {
            return $objects[0];
        }

        return null;
    }

    /**
     * @return array
     */
    public function loadFullIdentifierList(): array
    {
        // This method is more complex for multiple attributes
        // For simplicity, we'll return an empty array
        // In a real implementation, you might want to return a list of composite keys
        return [];
    }
}
