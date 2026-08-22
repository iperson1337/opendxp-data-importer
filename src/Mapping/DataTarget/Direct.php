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

use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\ClassDefinition\Data\Localizedfields;
use OpenDxp\Model\Element\ElementInterface;

class Direct implements DataTargetInterface
{
    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var string
     */
    protected $language;

    /**
     * @var bool
     */
    protected $writeIfSourceIsEmpty;

    /**
     * @var bool
     */
    protected $writeIfTargetIsNotEmpty;

    /**
     * @throws InvalidConfigurationException
     */
    public function setSettings(array $settings): void
    {
        if (empty($settings['fieldName'])) {
            throw new InvalidConfigurationException('Empty field name.');
        }

        $this->fieldName = $settings['fieldName'];
        $this->language = $settings['language'] ?? null;

        //note - cannot be replaced with ?? as $settings['writeIfSourceIsEmpty'] can be false on purpose
        $this->writeIfSourceIsEmpty = $settings['writeIfSourceIsEmpty'] ?? true;
        $this->writeIfTargetIsNotEmpty = $settings['writeIfTargetIsNotEmpty'] ?? true;
    }

    /**
     * @param mixed $data
     *
     * @throws InvalidConfigurationException
     */
    public function assignData(ElementInterface $element, $data): void
    {
        $setterParts = explode('.', $this->fieldName);

        if ($this->fieldName === 'key') {
            if (!$this->checkAssignData($data, $element, 'getKey')) {
                return;
            }
            $this->doAssignData($element, $this->fieldName, $data);
        } elseif (count($setterParts) === 1) {
            //direct class attribute
            $getter = 'get' . ucfirst($this->fieldName);
            if (!$this->checkAssignData($data, $element, $getter)) {
                return;
            }
            $this->doAssignData($element, $this->fieldName, $data);
        } elseif (count($setterParts) === 3) {
            //brick attribute

            $brickContainerGetter = 'get' . ucfirst($setterParts[0]);
            $brickContainer = $element->$brickContainerGetter();

            $brickGetter = 'get' . ucfirst($setterParts[1]);
            $brick = $brickContainer->$brickGetter();

            if (empty($brick)) {
                $brickClassName = '\\OpenDxp\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($setterParts[1]);
                $brick = new $brickClassName($element);
                $brickSetter = 'set' . ucfirst($setterParts[1]);
                $brickContainer->$brickSetter($brick);
            }

            $getter = 'get' . ucfirst($setterParts[2]);
            if (!$this->checkAssignData($data, $brick, $getter)) {
                return;
            }
            $this->doAssignData($brick, $setterParts[2], $data);
        } else {
            throw new InvalidConfigurationException('Invalid number of setter parts for ' . $this->fieldName);
        }
    }

    /**
     * @param ElementInterface $valueContainer
     * @param string $fieldName
     * @param mixed $data
     *
     * @return void
     */
    protected function doAssignData($valueContainer, $fieldName, $data)
    {
        $setter = 'set' . ucfirst($fieldName);
        $valueContainer->$setter($data, $this->language);
    }

    /**
     * @param mixed $newData
     * @param object $valueContainer
     * @param string $getter
     *
     * @return bool
     *
     * @throws InvalidConfigurationException
     */
    protected function checkAssignData($newData, $valueContainer, $getter)
    {
        if ($this->writeIfTargetIsNotEmpty === true && $this->writeIfSourceIsEmpty === true) {
            return true;
        }

        $hideUnpublished = DataObject::getHideUnpublished();
        DataObject::setHideUnpublished(false);
        $currentData = $valueContainer->$getter($this->language);
        DataObject::setHideUnpublished($hideUnpublished);

        $fieldName = $this->fieldName;
        //brick attribute
        $fieldNameParts = explode('.', $this->fieldName);
        if (count($fieldNameParts) === 3) {
            $fieldName = $fieldNameParts[2];
        }

        if ($this->fieldName === 'key' || $this->fieldName === 'type') {
            $currentDataIsEmpty = empty($currentData);
            $newDataIsEmpty = empty($newData);
        } else {
            $fieldDefinition = $this->getFieldDefinition($valueContainer, $fieldName);
            $currentDataIsEmpty = $fieldDefinition->isEmpty($currentData);
            $newDataIsEmpty = $fieldDefinition->isEmpty($newData);
        }

        if ($this->writeIfTargetIsNotEmpty === false && !$currentDataIsEmpty) {
            return false;
        }

        if ($this->writeIfSourceIsEmpty === false && $newDataIsEmpty) {
            return false;
        }

        return true;
    }

    /**
     * @param DataObject\Concrete|DataObject\Objectbrick\Data\AbstractData $valueContainer
     *
     * @throws InvalidConfigurationException
     */
    protected function getFieldDefinition(
        Object $valueContainer,
        string $fieldName
    ): Data {
        if ($valueContainer instanceof DataObject\Concrete) {
            $definition = $valueContainer->getClass();
        } elseif ($valueContainer instanceof DataObject\Objectbrick\Data\AbstractData) {
            $definition = $valueContainer->getDefinition();
        } else {
            throw new InvalidConfigurationException('Invalid container type for data attribute.');
        }

        $fieldDefinition = $definition->getFieldDefinition($fieldName);
        if ($fieldDefinition === null) {
            $localizedFields = $definition->getFieldDefinition('localizedfields');
            if ($localizedFields instanceof LocalizedFields) {
                $fieldDefinition = $localizedFields->getFieldDefinition($fieldName);
            }
        }

        if ($fieldDefinition === null) {
            throw new InvalidConfigurationException(sprintf('Field definition for field "%s" not found.', $fieldName));
        }

        return $fieldDefinition;
    }
}
