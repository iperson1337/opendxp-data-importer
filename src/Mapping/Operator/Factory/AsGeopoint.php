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
use OpenDxp\Model\DataObject\Data\GeoCoordinates;
use Override;

class AsGeopoint extends AbstractOperator
{
    /**
     * @param mixed $inputData
     *
     * @return GeoCoordinates
     */
    public function process($inputData, bool $dryRun = false)
    {
        return new GeoCoordinates($inputData[0] ?? null, $inputData[1] ?? null);
    }

    /**
     * @param mixed $inputData
     *
     * @return mixed|string
     */
    #[Override]
    public function generateResultPreview($inputData)
    {
        if ($inputData instanceof GeoCoordinates) {
            return 'Lat.: ' . $inputData->getLongitude() . '  Long.: ' . $inputData->getLatitude();
        }

        return $inputData;
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function evaluateReturnType(string $inputType, ?int $index = null): string
    {
        if ($inputType !== TransformationDataTypeService::DEFAULT_ARRAY) {
            throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for geoPoint operator at transformation position %s", $inputType, $index));
        }

        return TransformationDataTypeService::GEOPOINT_VALUE;
    }
}
