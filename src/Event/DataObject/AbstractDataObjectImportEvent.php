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

namespace OpenDxp\Bundle\DataImporterBundle\Event\DataObject;

use OpenDxp\Model\Element\ElementInterface;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractDataObjectImportEvent extends Event
{
    /**
     * @var string
     */
    protected $configName;

    /**
     * @var array
     */
    protected $rawData;

    /**
     * @var ElementInterface
     */
    protected $dataObject;

    /**
     * AbstractDataObjectImportEvent constructor.
     */
    public function __construct(string $configName, array $rawData, ElementInterface $dataObject)
    {
        $this->configName = $configName;
        $this->rawData = $rawData;
        $this->dataObject = $dataObject;
    }

    public function getConfigName(): string
    {
        return $this->configName;
    }

    public function setConfigName(string $configName): self
    {
        $this->configName = $configName;

        return $this;
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function setRawData(array $rawData): self
    {
        $this->rawData = $rawData;

        return $this;
    }

    public function getDataObject(): ElementInterface
    {
        return $this->dataObject;
    }

    public function setDataObject(ElementInterface $dataObject): self
    {
        $this->dataObject = $dataObject;

        return $this;
    }
}
