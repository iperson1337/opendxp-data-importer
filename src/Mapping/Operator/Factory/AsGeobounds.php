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
use OpenDxp\Model\DataObject\Data\Geobounds;
use OpenDxp\Model\DataObject\Data\GeoCoordinates;
use Override;

class AsGeobounds extends AbstractOperator
{
    /**
     * @param mixed $inputData
     *
     * @return Geobounds
     */
    public function process($inputData, bool $dryRun = false)
    {
        $northEast = new GeoCoordinates($inputData[0] ?? null, $inputData[1] ?? null);
        $southWest = new GeoCoordinates($inputData[2] ?? null, $inputData[3] ?? null);

        return new Geobounds($northEast, $southWest);
    }

    /**
     * @param mixed $inputData
     *
     * @return mixed|string
     */
    #[Override]
    public function generateResultPreview($inputData)
    {
        if ($inputData instanceof Geobounds) {
            return 'NE: ' . $inputData->getNorthEast() . ' SW: ' . $inputData->getSouthWest();
        }

        return $inputData;
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function evaluateReturnType(string $inputType, ?int $index = null): string
    {
        if ($inputType !== TransformationDataTypeService::DEFAULT_ARRAY) {
            throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for geoBounds operator at transformation position %s", $inputType, $index));
        }

        return TransformationDataTypeService::GEOBOUNDS_VALUE;
    }
}
