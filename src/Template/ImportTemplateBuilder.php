<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\DataImporterBundle\Template;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use RuntimeException;

/**
 * Пустой xlsx-шаблон импорта из его mappingConfig: заголовок колонки — `label` маппинга,
 * позиция — `dataSourceIndex`. Заголовки на импорт не влияют (`skipFirstRow`), они для человека.
 * Лист называется так, как ждёт интерпретатор — на чужом имени XlsxFileInterpreter падает.
 */
class ImportTemplateBuilder
{
    public const DEFAULT_SHEET_NAME = 'Sheet1';

    public const UNMAPPED_HEADER = '(не используется)';

    public function sheetName(array $config): string
    {
        return (string) ($config['interpreterConfig']['settings']['sheetName'] ?? self::DEFAULT_SHEET_NAME) ?: self::DEFAULT_SHEET_NAME;
    }

    /**
     * Колонки по позициям из `dataSourceIndex`. Позиции, которые конфиг не читает, заполняются
     * заглушкой: пустой заголовок в середине шапки — верный способ получить файл со сдвинутыми
     * колонками.
     *
     * @return array<int, string> непрерывный список заголовков от колонки 0 до последней читаемой
     */
    public function buildHeaders(array $config): array
    {
        $identifierIndex = $config['resolverConfig']['loadingStrategy']['settings']['dataSourceIndex'] ?? null;
        $byIndex = [];

        foreach ($config['mappingConfig'] ?? [] as $mapping) {
            $label = trim((string) ($mapping['label'] ?? ''));
            if ($label === '') {
                $label = (string) ($mapping['dataTarget']['settings']['fieldName'] ?? 'без названия');
            }

            $indexes = array_values((array) ($mapping['dataSourceIndex'] ?? []));
            foreach ($indexes as $position => $rawIndex) {
                $index = (int) $rawIndex;
                // Одна колонка может быть замаплена дважды — заголовки склеиваются через « / »
                $suffix = count($indexes) > 1 ? sprintf(' (%d из %d)', $position + 1, count($indexes)) : '';
                $title = $label . $suffix;

                $byIndex[$index] = isset($byIndex[$index]) ? $byIndex[$index] . ' / ' . $title : $title;
            }
        }

        // Колонка-идентификатор часто не замаплена ни в одно поле (нужна только для поиска)
        if ($identifierIndex !== null && $identifierIndex !== '') {
            $index = (int) $identifierIndex;
            $attribute = (string) ($config['resolverConfig']['loadingStrategy']['settings']['attributeName'] ?? '');
            $byIndex[$index] = ($byIndex[$index] ?? ($attribute !== '' ? $attribute : 'Идентификатор')) . ' [идентификатор]';
        }

        if ($byIndex === []) {
            return [];
        }

        $headers = [];
        for ($i = 0, $last = max(array_keys($byIndex)); $i <= $last; $i++) {
            $headers[$i] = $byIndex[$i] ?? self::UNMAPPED_HEADER;
        }

        return $headers;
    }

    /**
     * @param array<int, string> $headers
     */
    public function buildXlsx(array $headers, string $sheetName): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetName);

        $columnIndex = 1;
        foreach ($headers as $header) {
            $sheet->setCellValue([$columnIndex, 1], $header);
            $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
            $columnIndex++;
        }

        $sheet->getStyle([1, 1, max(1, count($headers)), 1])->getFont()->setBold(true);
        $sheet->freezePane('A2');

        $tempFile = tempnam(sys_get_temp_dir(), 'import-template-') ?: null;
        if ($tempFile === null) {
            throw new RuntimeException('Не удалось создать временный файл для xlsx');
        }

        try {
            (new XlsxWriter($spreadsheet))->save($tempFile);
            $content = file_get_contents($tempFile);
        } finally {
            @unlink($tempFile);
            $spreadsheet->disconnectWorksheets();
        }

        if ($content === false || $content === '') {
            throw new RuntimeException('Пустой результат записи xlsx');
        }

        return $content;
    }

    public function buildFromConfig(array $config): string
    {
        return $this->buildXlsx($this->buildHeaders($config), $this->sheetName($config));
    }
}
