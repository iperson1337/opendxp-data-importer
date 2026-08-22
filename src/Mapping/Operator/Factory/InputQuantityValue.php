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
use OpenDxp\Bundle\DataImporterBundle\Mapping\Type\TransformationDataTypeService;
use OpenDxp\Model\DataObject\QuantityValue\Unit;
use Override;

class InputQuantityValue extends QuantityValue
{
    /**
     * @param mixed $inputData
     *
     * @return \OpenDxp\Model\DataObject\Data\InputQuantityValue
     */
    #[Override]
    public function process($inputData, bool $dryRun = false)
    {
        $unit = isset($inputData[1]) ? Unit::getByAbbreviation($inputData[1]) : null;

        return new \OpenDxp\Model\DataObject\Data\InputQuantityValue(
            $inputData[0] ?? null,
            $unit
        );
    }

    /**
     * @throws InvalidConfigurationException
     */
    #[Override]
    public function evaluateReturnType(string $inputType, ?int $index = null): string
    {
        if ($inputType !== TransformationDataTypeService::DEFAULT_ARRAY) {
            throw new InvalidConfigurationException(
                sprintf(
                    "Unsupported input type '%s' for input quantity value operator at transformation position %s",
                    $inputType,
                    $index)
            );
        }

        return TransformationDataTypeService::INPUT_QUANTITY_VALUE;
    }

    /**
     * @param mixed $inputData
     *
     * @return string
     */
    #[Override]
    public function generateResultPreview($inputData)
    {
        if ($inputData instanceof \OpenDxp\Model\DataObject\Data\InputQuantityValue) {
            return 'InputQuantityValue: ' .
                $inputData->getValue() .
                ($inputData->getUnit() ? ' ['.$inputData->getUnit()->getAbbreviation().']' : '');
        }

        return $inputData;
    }
}
