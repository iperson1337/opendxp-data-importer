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

class ConditionalConversion extends AbstractOperator
{
    /**
     * @var string
     */
    protected $original;

    /**
     * @var string
     */
    protected $converted;

    #[Override]
    public function setSettings(array $settings): void
    {
        $this->original = $settings['original'] ?? '';
        $this->converted = $settings['converted'] ?? '';
    }

    /**
     * @param mixed $inputData
     *
     * @return array|false|mixed|null
     */
    public function process($inputData, bool $dryRun = false)
    {
        $returnScalar = false;
        if (!is_array($inputData)) {
            $returnScalar = true;
            $inputData = [$inputData];
        }

        $origArr = explode('|', $this->original);
        $convArr = explode('|', $this->converted);
        foreach ($inputData as &$data) {
            $index = array_search($data, $origArr);
            if ($index !== false) {
                $data = $convArr[$index];
            } else {
                $index = array_search('*', $origArr);
                if ($index !== false) {
                    $data = $convArr[$index];
                }
            }
        }

        if ($returnScalar) {
            if (!empty($inputData)) {
                return reset($inputData);
            }

            return null;
        } else {
            return $inputData;
        }
    }

    public function evaluateReturnType(string $inputType, ?int $index = null): string
    {
        if (!in_array($inputType, [TransformationDataTypeService::DEFAULT_TYPE, TransformationDataTypeService::DEFAULT_ARRAY])) {
            throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for simple test operator at transformation position %s", $inputType, $index));
        }

        return $inputType;
    }
}
