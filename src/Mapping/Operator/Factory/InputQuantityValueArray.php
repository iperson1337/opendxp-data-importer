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
use Override;

class InputQuantityValueArray extends QuantityValueArray
{
    /**
     * @param mixed $inputData
     *
     * @return array
     */
    #[Override]
    public function process($inputData, bool $dryRun = false)
    {
        if (!is_array($inputData)) {
            return [];
        }

        $result = [];

        foreach ($inputData as $key => $data) {
            $result[$key] = new \OpenDxp\Model\DataObject\Data\InputQuantityValue(
                $data[0] ?? null,
                $data[1] ?? null
            );
        }

        return $result;
    }

    /**
     * @throws InvalidConfigurationException
     */
    #[Override]
    public function evaluateReturnType(string $inputType, ?int $index = null): string
    {
        if ($inputType !== TransformationDataTypeService::DEFAULT_ARRAY) {
            throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for input quantity value operator at transformation position %s", $inputType, $index));
        }

        return TransformationDataTypeService::INPUT_QUANTITY_VALUE_ARRAY;
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
                if ($data instanceof \OpenDxp\Model\DataObject\Data\InputQuantityValue) {
                    $preview[$key] = 'InputQuantityValue: ' . $data->getValue() . ' ' . ($data->getUnit() ? $data->getUnit()->getAbbreviation() : '');
                } else {
                    $preview[$key] = $data;
                }
            }

            return $preview;
        }

        return $inputData;
    }
}
