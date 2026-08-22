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

class StaticText extends AbstractOperator
{
    const MODE_APPEND = 'append';

    const MODE_PREPEND = 'prepend';

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var string
     */
    protected $text;

    /**
     * @var bool
     */
    protected $alwaysAdd;

    #[Override]
    public function setSettings(array $settings): void
    {
        $this->mode = $settings['mode'] ?? self::MODE_APPEND;
        $this->text = $settings['text'] ?? '';
        $this->alwaysAdd = $settings['alwaysAdd'] ?? false;
    }

    /**
     * @param mixed $inputData
     *
     * @return array|false|mixed|null
     *
     * @throws InvalidConfigurationException
     */
    public function process($inputData, bool $dryRun = false)
    {
        $returnScalar = false;
        if (!is_array($inputData)) {
            $returnScalar = true;
            $inputData = [$inputData];
        }

        if ($this->text !== '') {
            foreach ($inputData as &$data) {
                if (!empty($data) || $this->alwaysAdd) {
                    $data = match ($this->mode) {
                        self::MODE_APPEND => $data . $this->text,
                        self::MODE_PREPEND => $this->text . $data,
                        default => throw new InvalidConfigurationException(sprintf('Invalid mode: %s', $this->mode)),
                    };
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
            throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for static t ext operator at transformation position %s", $inputType, $index));
        }

        return $inputType;
    }
}
