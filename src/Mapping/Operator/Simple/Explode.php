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

class Explode extends AbstractOperator
{
    /**
     * @var string
     */
    protected $delimiter;

    /**
     * @var bool
     */
    protected $keepSubArrays;

    #[Override]
    public function setSettings(array $settings): void
    {
        $this->delimiter = $settings['delimiter'] ?? ' ';
        $this->keepSubArrays = (bool) ($settings['keepSubArrays'] ?? false);
    }

    /**
     * @param mixed $inputData
     *
     * @return array|array[]|mixed|string[]|\string[][]
     */
    public function process($inputData, bool $dryRun = false)
    {
        if (empty($inputData)) {
            return [];
        }
        if (!empty($this->delimiter)) {
            if (is_array($inputData)) {
                $explodedArray = [];
                foreach ($inputData as $key => $dataRow) {
                    if ($this->keepSubArrays) {
                        $explodedArray[$key] = $this->process($dataRow, $dryRun);
                    } else {
                        $explodedArray = array_merge($explodedArray, [$this->process($dataRow, $dryRun)]);
                    }
                }

                return $explodedArray;
            } else {
                return explode($this->delimiter, (string) $inputData);
            }
        } else {
            return [$inputData];
        }
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function evaluateReturnType(string $inputType, ?int $index = null): string
    {
        if (! in_array($inputType, [TransformationDataTypeService::DEFAULT_TYPE, TransformationDataTypeService::DEFAULT_ARRAY])) {
            throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for explode operator at transformation position %s", $inputType, $index));
        }

        return TransformationDataTypeService::DEFAULT_ARRAY;
    }
}
