<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\Simple;

use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\AbstractOperator;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Type\TransformationDataTypeService;
use OpenDxp\Model\DataObject\Data\NumericRange;

/**
 * Combines two source columns (minimum, maximum) into a NumericRange value object,
 * the type required by NumericRange-typed fields (e.g. Product::storageTemperature).
 */
class NumericRangeCombine extends AbstractOperator
{
    /**
     * @param mixed $inputData
     * @param bool $dryRun
     *
     * @return NumericRange|null
     */
    public function process($inputData, bool $dryRun = false)
    {
        $values = is_array($inputData) ? array_values($inputData) : [$inputData];

        $minimum = $this->toFloatOrNull($values[0] ?? null);
        $maximum = $this->toFloatOrNull($values[1] ?? null);

        if ($minimum === null && $maximum === null) {
            return null;
        }

        return new NumericRange($minimum, $maximum);
    }

    /**
     * @param string $inputType
     * @param int|null $index
     *
     * @return string
     *
     * @throws InvalidConfigurationException
     */
    public function evaluateReturnType(string $inputType, int $index = null): string
    {
        if ($inputType !== TransformationDataTypeService::DEFAULT_ARRAY) {
            throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for numericRange operator at transformation position %s", $inputType, $index));
        }

        return TransformationDataTypeService::NUMERIC_RANGE;
    }

    private function toFloatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
