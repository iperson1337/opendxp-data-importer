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

namespace OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\Factory;

use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\AbstractOperator;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Type\TransformationDataTypeService;

class Time extends AbstractOperator
{
    protected string $format;

    public function setSettings(array $settings): void
    {
        $this->format = $settings['format'] ?? 'H:i';
    }

    /**
     * @param mixed $inputData
     * @param bool $dryRun
     *
     * @return array|false|mixed
     */
    public function process($inputData, bool $dryRun = false): mixed
    {
        $returnScalar = false;
        if (!is_array($inputData)) {
            $returnScalar = true;
            $inputData = [$inputData];
        }

        foreach ($inputData as &$data) {
            if (!empty($data)) {
                $carbon = \Carbon\Carbon::createFromFormat($this->format, $data);
                $data = $carbon ? $carbon->format('H:i') : null;
            } else {
                $data = null;
            }
        }

        if ($returnScalar) {
            return reset($inputData);
        }

        return $inputData;
    }

    /**
     * @param string $inputType
     * @param int|null $index
     *
     * @return string
     *
     * @throws InvalidConfigurationException
     */
    public function evaluateReturnType(string $inputType, ?int $index = null): string
    {
        if (!in_array($inputType, [TransformationDataTypeService::DEFAULT_TYPE, TransformationDataTypeService::DEFAULT_ARRAY], true)) {
            throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for time operator at transformation position %s", $inputType, $index));
        }

        if ($inputType === TransformationDataTypeService::DEFAULT_ARRAY) {
            return TransformationDataTypeService::TIME_ARRAY;
        }

        return TransformationDataTypeService::TIME;
    }

    /**
     * @param mixed $inputData
     *
     * @return array|mixed|string
     */
    public function generateResultPreview($inputData): mixed
    {
        if (is_array($inputData)) {
            $preview = [];

            foreach ($inputData as $key => $data) {
                if ($data instanceof \DateTime) {
                    $preview[$key] = $data->format('H:i');
                } else {
                    $preview[$key] = $data;
                }
            }

            return $preview;
        }

        if ($inputData instanceof \DateTime) {
            return $inputData->format('H:i');
        }

        return $inputData;
    }
}
