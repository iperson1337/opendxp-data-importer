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
use OpenDxp\Model\DataObject\QuantityValue\Unit;
use Override;

class QuantityValue extends AbstractOperator
{
    /**
     * @var string
     */
    protected $unitSource = 'id';

    /**
     * @var string
     */
    protected $staticUnitId;

    /**
     * @var bool
     */
    protected $unitNullIfNoValue;

    #[Override]
    public function setSettings(array $settings): void
    {
        $this->unitSource = $settings['unitSourceSelect'] ?? 'id';
        $this->staticUnitId = $settings['staticUnitSelect'] ?? null;
        $this->unitNullIfNoValue = (bool) ($settings['unitNullIfNoValueCheckbox'] ?? false);
    }

    /**
     * @param mixed $inputData
     *
     * @return \OpenDxp\Model\DataObject\Data\AbstractQuantityValue|null
     */
    public function process($inputData, bool $dryRun = false)
    {
        $value = null;
        $unitId = null;

        switch ($this->unitSource) {
            case 'id':
                if (is_array($inputData)) {
                    if (isset($inputData[1])) {
                        $unit = Unit::getById($inputData[1]);
                        if ($unit instanceof Unit) {
                            $unitId = $unit->getId();
                        }
                    }
                    $value = $inputData[0] ?? null;
                }

                break;

            case 'abbr':
                if (is_array($inputData)) {
                    if (isset($inputData[1])) {
                        $unit = Unit::getByAbbreviation($inputData[1]);
                        if ($unit instanceof Unit) {
                            $unitId = $unit->getId();
                        }
                    }
                    $value = $inputData[0] ?? null;
                }

                break;

            case 'static':
                $value = $inputData;
                if (is_array($inputData)) {
                    $value = $inputData[0] ?? null;
                }
                $unitId = $this->staticUnitId;
        }

        $value ??= null;
        if (($value === null || $value === '') && $this->unitNullIfNoValue) {
            $unitId = null;
        }
        if (($value === null || $value === '') && $unitId === null) {
            return null;
        }

        return new \OpenDxp\Model\DataObject\Data\QuantityValue(
            $value === null ? null : floatval($value),
            $unitId ?? null
        );
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function evaluateReturnType(string $inputType, ?int $index = null): string
    {
        if ($this->unitSource !== 'static') {
            if ($inputType !== TransformationDataTypeService::DEFAULT_ARRAY) {
                throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for quantity value operator at transformation position %s",
                    $inputType, $index));
            }
        } elseif ($inputType !== TransformationDataTypeService::DEFAULT_TYPE) {
            throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for quantity value operator with static unit at transformation position %s",
                $inputType, $index));
        }

        return TransformationDataTypeService::QUANTITY_VALUE;
    }

    /**
     * @param mixed $inputData
     *
     * @return mixed|string
     */
    #[Override]
    public function generateResultPreview($inputData)
    {
        if ($inputData instanceof \OpenDxp\Model\DataObject\Data\QuantityValue) {
            return 'QuantityValue: ' . $inputData->getValue() . ' ' .
                ($inputData->getUnit() ? $inputData->getUnit()->getAbbreviation() : '');
        }

        return $inputData;
    }
}
