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
use Override;

class QuantityValueArray extends AbstractOperator
{
    /**
     * @param mixed $inputData
     *
     * @return array
     */
    public function process($inputData, bool $dryRun = false)
    {
        if (!is_array($inputData)) {
            return [];
        }

        $result = [];

        foreach ($inputData as $key => $data) {
            $value = $data[0] ?? null;
            $unitId = $data[1] ?? null;
            if (($value === null || $value === '') && $unitId === null) {
                $result[$key] = null;

                continue;
            }
            $result[$key] = new \OpenDxp\Model\DataObject\Data\QuantityValue(
                $value === null ? null : floatval($value),
                $unitId
            );
        }

        return $result;
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function evaluateReturnType(string $inputType, ?int $index = null): string
    {
        if ($inputType !== TransformationDataTypeService::DEFAULT_ARRAY) {
            throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for quantity value operator at transformation position %s", $inputType, $index));
        }

        return TransformationDataTypeService::QUANTITY_VALUE_ARRAY;
    }

    /**
     * @param mixed $inputData
     *
     * @return array|mixed
     */
    #[Override]
    public function generateResultPreview($inputData)
    {
        if (is_array($inputData)) {
            $preview = [];

            foreach ($inputData as $key => $data) {
                if ($data instanceof \OpenDxp\Model\DataObject\Data\QuantityValue) {
                    $preview[$key] = 'QuantityValue: ' . $data->getValue() . ' ' . ($data->getUnit() ? $data->getUnit()->getAbbreviation() : '');
                } else {
                    $preview[$key] = $data;
                }
            }

            return $preview;
        }

        return $inputData;
    }
}
