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

namespace OpenDxp\Bundle\DataImporterBundle\Mapping\DataTarget;

use Exception;
use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Data\ElementMetadata;
use OpenDxp\Model\DataObject\Data\ObjectMetadata;
use OpenDxp\Model\Element\Service;
use Override;

class ManyToManyRelation extends Direct
{
    const OVERWRITE_MODE_MERGE = 'merge';

    const OVERWRITE_MODE_REPLACE = 'replace';

    /**
     * @var bool
     */
    protected $overwriteMode;

    /**
     * @throws InvalidConfigurationException
     */
    #[Override]
    public function setSettings(array $settings): void
    {
        parent::setSettings($settings);
        $this->overwriteMode = $settings['overwriteMode'] ?? self::OVERWRITE_MODE_REPLACE;
    }

    /**
     * @param DataObject\Concrete|DataObject\Objectbrick\Data\AbstractData $valueContainer
     * @param string $fieldName
     * @param mixed $data
     *
     * @return void
     *
     * @throws InvalidConfigurationException
     */
    #[Override]
    protected function doAssignData($valueContainer, $fieldName, $data)
    {
        $fieldDefinition = $this->getFieldDefinition($valueContainer, $fieldName);

        switch ($fieldDefinition->getFieldtype()) {
            case 'manyToManyRelation':
            case 'manyToManyObjectRelation':
            case 'advancedManyToManyRelation':
            case 'advancedManyToManyObjectRelation':

                $setter = 'set' . ucfirst($fieldName);
                $getter = 'get' . ucfirst($fieldName);
                $valueContainer->$setter(
                    $this->getMergedDataArray($valueContainer, $getter, $fieldDefinition->getFieldtype(), $data),
                    $this->language
                );

                break;

            default:
                throw new InvalidConfigurationException('Invalid field type for attribute ' . $fieldName .
                    '. Only supports advanced relation types, ' . $fieldDefinition->getFieldtype() . ' given.');
        }
    }

    /**
     * @param object $valueContainer
     * @param mixed $data
     *
     * @throws Exception
     */
    protected function getMergedDataArray($valueContainer, string $getter, string $fieldType, $data): array
    {
        if (null === $data) {
            return [];
        }

        $currentData = [];
        if ($this->overwriteMode == self::OVERWRITE_MODE_MERGE) {
            $hideUnpublished = DataObject::getHideUnpublished();
            DataObject::setHideUnpublished(false);
            $currentData = $valueContainer->$getter($this->language);
            DataObject::setHideUnpublished($hideUnpublished);
        }

        $newData = [];
        switch ($fieldType) {
            case 'manyToManyObjectRelation':
                if ($this->overwriteMode == self::OVERWRITE_MODE_MERGE) {
                    foreach ($currentData as $dataObject) {
                        $newData[$dataObject->getId()] = $dataObject;
                    }

                    foreach ($data as $dataObject) {
                        if (!isset($newData[$dataObject->getId()])) {
                            $newData[$dataObject->getId()] = $dataObject;
                        }
                    }
                } else {
                    return is_array($data) ? $data : [$data];
                }

                break;

            case 'advancedManyToManyObjectRelation':
                if ($this->overwriteMode == self::OVERWRITE_MODE_MERGE) {
                    foreach ($currentData as $metaDataObject) {
                        $newData[$metaDataObject->getObject()->getId()] = $metaDataObject;
                    }
                }
                foreach ($data as $dataObject) {
                    if ($this->overwriteMode == self::OVERWRITE_MODE_REPLACE || !isset($newData[$dataObject->getId()])) {
                        $metaDataObject = new ObjectMetadata($this->fieldName, [], $dataObject);
                        $newData[$metaDataObject->getObject()->getId()] = $metaDataObject;
                    }
                }

                break;

            case 'manyToManyRelation':
                if ($this->overwriteMode == self::OVERWRITE_MODE_MERGE) {
                    foreach ($currentData as $element) {
                        $newData[Service::getElementType($element) . '_' . $element->getId()] = $element;
                    }
                    foreach ($data as $element) {
                        if (!isset($newData[Service::getElementType($element) . '_' . $element->getId()])) {
                            $newData[Service::getElementType($element) . '_' . $element->getId()] = $element;
                        }
                    }
                } else {
                    return is_array($data) ? $data : [$data];
                }

                break;

            case 'advancedManyToManyRelation':
                if ($this->overwriteMode == self::OVERWRITE_MODE_MERGE) {
                    foreach ($currentData as $metaDataElement) {
                        $newData[Service::getElementType($metaDataElement->getElement()) . '_' .
                        $metaDataElement->getElement()->getId()] = $metaDataElement;
                    }
                }
                foreach ($data as $element) {
                    if ($this->overwriteMode == self::OVERWRITE_MODE_REPLACE ||
                        !isset($newData[Service::getElementType($element) . '_' . $element->getId()])) {
                        $metaDataElement = new ElementMetadata($this->fieldName, [], $element);
                        $newData[Service::getElementType($metaDataElement->getElement()) . '_' . $element->getId()] =
                            $metaDataElement;
                    }
                }

                break;

        }

        return array_values($newData);
    }
}
