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

namespace OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\Simple;

use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\AbstractOperator;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Type\TransformationDataTypeService;
use Override;

class ReduceArrayKeyValuePairs extends AbstractOperator
{
    #[Override]
    public function setSettings(array $settings): void
    {
        //nothing to do
    }

    /**
     * @param mixed $inputData
     *
     * @return array
     */
    public function process($inputData, bool $dryRun = false)
    {
        if (!is_array($inputData) && !empty($inputData)) {
            $inputData = [$inputData];
        }

        $reducedArray = [];
        while (!empty($inputData)) {
            $reducedArray[array_shift($inputData)] = array_shift($inputData);
        }

        return $reducedArray;
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function evaluateReturnType(string $inputType, ?int $index = null): string
    {
        if ($inputType !== TransformationDataTypeService::DEFAULT_ARRAY) {
            throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for reduce array key value pairs operator at transformation position %s", $inputType, $index));
        }

        return TransformationDataTypeService::DEFAULT_ARRAY;
    }
}
