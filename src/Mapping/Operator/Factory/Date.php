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

use DateTime;
use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\AbstractOperator;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Type\TransformationDataTypeService;
use Override;

class Date extends AbstractOperator
{
    /**
     * @var string
     */
    protected $format;

    #[Override]
    public function setSettings(array $settings): void
    {
        $this->format = $settings['format'] ?? 'Y-m-d';
    }

    /**
     * @param mixed $inputData
     *
     * @return array|false|mixed
     */
    public function process($inputData, bool $dryRun = false)
    {
        $returnScalar = false;
        if (!is_array($inputData)) {
            $returnScalar = true;
            $inputData = [$inputData];
        }

        foreach ($inputData as &$data) {
            if (!empty($data)) {
                $data = \Carbon\Carbon::createFromFormat($this->format, $data);
            } else {
                $data = null;
            }
        }

        if ($returnScalar) {
            return reset($inputData);
        } else {
            return $inputData;
        }
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function evaluateReturnType(string $inputType, ?int $index = null): string
    {
        if (!in_array($inputType, [TransformationDataTypeService::DEFAULT_TYPE, TransformationDataTypeService::DEFAULT_ARRAY])) {
            throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for date operator at transformation position %s", $inputType, $index));
        }

        if ($inputType === TransformationDataTypeService::DEFAULT_ARRAY) {
            return TransformationDataTypeService::DATE_ARRAY;
        }

        return TransformationDataTypeService::DATE;
    }

    /**
     * @param mixed $inputData
     *
     * @return array|mixed|string
     */
    #[Override]
    public function generateResultPreview($inputData)
    {
        if ($inputData instanceof DateTime) {
            return $inputData->format('c');
        }

        if (is_array($inputData)) {
            $preview = [];

            foreach ($inputData as $key => $data) {
                if ($data instanceof DateTime) {
                    $preview[$key] = $data->format('c');
                } else {
                    $preview[$key] = $data;
                }
            }

            return $preview;
        }

        return $inputData;
    }
}
