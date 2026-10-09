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
use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidInputException;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\AbstractOperator;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Type\TransformationDataTypeService;
use Override;

/**
 * Проверяет значение колонки как число и отклоняет строку импорта целиком, если оно не
 * подходит: исключение из конвейера попадает в обработку ошибок строки — объект не
 * сохраняется, причина пишется в лог импорта («Отклонённые»).
 *
 * Значение не преобразует (кроме обрезки пробелов у строки) — превращать в число дальше
 * должен следующий оператор (numeric, quantityValue …). Без проверки они молча делают из
 * «abc» ноль, а из «12.5» — дробь.
 *
 * Дробь через запятую («12,5») распознаётся как число: Excel с русской локалью пишет её так.
 */
class ValidateNumber extends AbstractOperator
{
    private bool $required = true;

    private bool $integerOnly = false;

    private ?float $greaterThan = null;

    private string $label = '';

    #[Override]
    public function setSettings(array $settings): void
    {
        $this->required = ($settings['required'] ?? 'on') === 'on' || ($settings['required'] ?? null) === true;
        $this->integerOnly = ($settings['integerOnly'] ?? null) === 'on' || ($settings['integerOnly'] ?? null) === true;
        $greaterThan = trim((string) ($settings['greaterThan'] ?? ''));
        $this->greaterThan = is_numeric($greaterThan) ? (float) $greaterThan : null;
        $this->label = trim((string) ($settings['label'] ?? ''));
    }

    /**
     * @param mixed $inputData
     *
     * @return mixed
     *
     * @throws InvalidInputException
     */
    public function process($inputData, bool $dryRun = false)
    {
        $value = is_string($inputData) ? trim($inputData) : $inputData;

        $error = $this->validate($value);
        if ($error !== null && !$dryRun) {
            throw new InvalidInputException(($this->label !== '' ? $this->label . ': ' : '') . $error);
        }

        return $value;
    }

    /**
     * @return string|null текст причины отказа, null — значение подходит
     */
    public function validate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return $this->required ? 'значение не заполнено' : null;
        }

        $normalized = is_string($value) ? str_replace(',', '.', $value) : $value;
        if (!is_numeric($normalized)) {
            return sprintf('«%s» — не число', is_scalar($value) ? $value : get_debug_type($value));
        }

        $number = (float) $normalized;
        if ($this->integerOnly && $number !== floor($number)) {
            return sprintf('%s — дробное значение запрещено, нужно целое', $value);
        }

        if ($this->greaterThan !== null && $number <= $this->greaterThan) {
            return sprintf('%s — значение должно быть больше %s', $value, $this->greaterThan + 0);
        }

        return null;
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function evaluateReturnType(string $inputType, ?int $index = null): string
    {
        if ($inputType !== TransformationDataTypeService::DEFAULT_TYPE) {
            throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for validate number operator at transformation position %s", $inputType, $index));
        }

        return TransformationDataTypeService::DEFAULT_TYPE;
    }

    /**
     * В предпросмотре вместо исключения показывает причину отказа рядом со значением.
     *
     * @param mixed $inputData
     *
     * @return mixed
     */
    #[Override]
    public function generateResultPreview($inputData)
    {
        $error = $this->validate($inputData);

        return $error === null ? $inputData : '✗ ' . $error;
    }
}
