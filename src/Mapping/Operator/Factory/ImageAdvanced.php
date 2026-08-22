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

namespace OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\Factory;

use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\AbstractOperator;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Type\TransformationDataTypeService;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Data\Hotspotimage;
use Override;

class ImageAdvanced extends AbstractOperator
{
    /**
     * @param mixed $inputData
     *
     * @return Hotspotimage|null
     */
    public function process($inputData, bool $dryRun = false)
    {
        if (is_array($inputData)) {
            $inputData = reset($inputData);
        }

        if ($inputData instanceof Asset\Image) {
            return new Hotspotimage($inputData);
        }

        return null;
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function evaluateReturnType(string $inputType, ?int $index = null): string
    {
        if (!in_array($inputType, [TransformationDataTypeService::ASSET])) {
            throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for image advanced operator at transformation position %s", $inputType, $index));
        }

        return TransformationDataTypeService::IMAGE_ADVANCED;
    }

    /**
     * @param mixed $inputData
     *
     * @return mixed|string
     */
    #[Override]
    public function generateResultPreview($inputData)
    {
        if ($inputData instanceof Hotspotimage) {
            return 'Image Advanced: ' . ($inputData->getImage() ? $inputData->getImage()->getFullPath() : '');
        }

        return $inputData;
    }
}
