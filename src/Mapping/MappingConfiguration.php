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

use OpenDxp\Bundle\DataImporterBundle\Mapping\DataTarget\DataTargetInterface;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\OperatorInterface;

class MappingConfiguration
{
    /**
     * @var string
     */
    protected $label;

    /**
     * @var mixed
     */
    protected $dataSourceIndex;

    /**
     * @var array
     */
    protected $transformationPipeline;

    /**
     * @var DataTargetInterface
     */
    protected $dataTarget;

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label): void
    {
        $this->label = $label;
    }

    /**
     * @return mixed
     */
    public function getDataSourceIndex()
    {
        return $this->dataSourceIndex;
    }

    /**
     * @param mixed $dataSourceIndex
     */
    public function setDataSourceIndex($dataSourceIndex): void
    {
        $this->dataSourceIndex = $dataSourceIndex;
    }

    /**
     * @return OperatorInterface[]
     */
    public function getTransformationPipeline(): array
    {
        return $this->transformationPipeline;
    }

    /**
     * @param OperatorInterface[] $transformationPipeline
     */
    public function setTransformationPipeline($transformationPipeline): void
    {
        $this->transformationPipeline = $transformationPipeline;
    }

    public function getDataTarget(): DataTargetInterface
    {
        return $this->dataTarget;
    }

    /**
     * @param DataTargetInterface $dataTarget
     */
    public function setDataTarget($dataTarget): void
    {
        $this->dataTarget = $dataTarget;
    }
}
