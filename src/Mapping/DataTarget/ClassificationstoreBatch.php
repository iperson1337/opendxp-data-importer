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
use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidInputException;
use OpenDxp\Model\Element\ElementInterface;

class ClassificationstoreBatch implements DataTargetInterface
{
    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var string
     */
    protected $language;

    public function setSettings(array $settings): void
    {
        if (empty($settings['fieldName'])) {
            throw new InvalidConfigurationException('Empty field name.');
        }

        $this->fieldName = $settings['fieldName'];
        $this->language = $settings['language'] ?? null;
    }

    /**
     * @param mixed $data
     *
     * @return void
     *
     * @throws InvalidConfigurationException
     * @throws InvalidInputException
     */
    public function assignData(ElementInterface $element, $data)
    {
        $getter = 'get' . ucfirst($this->fieldName);
        $classificationStore = $element->$getter();

        if ($classificationStore instanceof \OpenDxp\Model\DataObject\Classificationstore) {
            if (!is_array($data)) {
                throw new InvalidInputException('Input data not an array');
            }

            $data = array_filter($data);
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $keyParts = explode('-', (string) $key);
                    if (count($keyParts) !== 2) {
                        throw new InvalidInputException('Key not format <GROUP_ID>-<KEY_ID>: ' . $key);
                    }

                    if (!is_numeric($keyParts[0])) {
                        throw new Exception('groupId not valid');
                    }

                    if (!is_numeric($keyParts[1])) {
                        throw new Exception('keyId not valid');
                    }

                    $classificationStore->setLocalizedKeyValue((int)$keyParts[0], (int)$keyParts[1], $value, $this->language);
                    $classificationStore->setActiveGroups($classificationStore->getActiveGroups() + [$keyParts[0] => true]);
                }
            }
        } else {
            throw new InvalidConfigurationException('Field ' . $this->fieldName . ' is not a classification store.');
        }
    }
}
