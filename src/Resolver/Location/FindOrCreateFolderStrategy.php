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

namespace OpenDxp\Bundle\DataImporterBundle\Resolver\Location;

use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Bundle\DataImporterBundle\Tool\DataObjectLoader;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Service;
use OpenDxp\Model\Element\ElementInterface;

class FindOrCreateFolderStrategy implements LocationStrategyInterface
{
    /**
     * @var mixed
     */
    protected $dataSourceIndex;

    /**
     * @var string
     */
    protected $findStrategy;

    /**
     * @var string
     */
    protected $fallbackPath;

    /**
     * @var mixed
     */
    protected $attributeDataObjectClassId;

    /**
     * @var string
     */
    protected $attributeName;

    /**
     * @var string
     */
    protected $attributeLanguage;

    public function __construct(protected DataObjectLoader $dataObjectLoader)
    {
    }

    public function setSettings(array $settings): void
    {
        if ($settings['dataSourceIndex'] !== 0 && $settings['dataSourceIndex'] !== '0' && empty($settings['dataSourceIndex'])) {
            throw new InvalidConfigurationException('Empty data source index.');
        }

        $this->dataSourceIndex = $settings['dataSourceIndex'];

        $this->fallbackPath = $settings['fallbackPath'] ?? null;
    }

    public function updateParent(ElementInterface $element, array $inputData): ElementInterface
    {
        $newParent = null;
        $identifier = $inputData[$this->dataSourceIndex] ?? null;

        if ($identifier) {
            $newParent = $this->dataObjectLoader->loadByPath($identifier);

            if (!($newParent instanceof DataObject)) {
                $newParent = Service::createFolderByPath($identifier);
            }
        }

        if (!($newParent instanceof DataObject) && $this->fallbackPath) {
            $newParent = DataObject::getByPath($this->fallbackPath);
        }

        if (!($newParent)) {
            $newParent = DataObject::getById(1);
        }

        return $element->setParent($newParent);
    }

    protected function loadById()
    {
    }
}
