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

namespace OpenDxp\Bundle\DataImporterBundle\DataSource\Interpreter;

use OpenDxp\Bundle\DataImporterBundle\Preview\Model\PreviewData;
use PhpOffice\PhpSpreadsheet\IOFactory;

class XlsxFileInterpreter extends AbstractInterpreter
{
    /**
     * @var bool
     */
    protected $skipFirstRow;

    /**
     * @var string
     */
    protected $sheetName;

    protected function doInterpretFileAndCallProcessRow(string $path): void
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadSheet = $reader->load($path);

        $spreadSheet->setActiveSheetIndexByName($this->sheetName);

        $data = $spreadSheet->getActiveSheet()->toArray();

        if ($this->skipFirstRow) {
            array_shift($data);
        }

        foreach ($data as $rowData) {
            $this->processImportRow($rowData);
        }
    }

    public function fileValid(string $path, bool $originalFilename = false): bool
    {
        $reader = IOFactory::createReaderForFile($path);

        return $reader->canRead($path);
    }

    public function previewData(string $path, int $recordNumber = 0, array $mappedColumns = []): PreviewData
    {
        $previewData = [];
        $columns = [];
        $readRecordNumber = 0;

        if ($this->fileValid($path)) {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadSheet = $reader->load($path);

            $spreadSheet->setActiveSheetIndexByName($this->sheetName);

            $data = $spreadSheet->getActiveSheet()->toArray();

            if ($this->skipFirstRow) {
                $firstRow = array_shift($data);
                foreach ($firstRow as $index => $columnHeader) {
                    $columns[$index] = trim((string) $columnHeader) . " [$index]";
                }
            }

            $previewDataRow = $data[$recordNumber] ?? null;

            if (empty($previewDataRow)) {
                $previewDataRow = end($data);
                $readRecordNumber = count($data) - 1;
            } else {
                $readRecordNumber = $recordNumber;
            }

            foreach ($previewDataRow as $index => $columnData) {
                $previewData[$index] = $columnData;
            }

            if (empty($columns)) {
                $columns = array_keys($previewData);
            }
        }

        return new PreviewData($columns, $previewData, $readRecordNumber, $mappedColumns);
    }

    public function setSettings(array $settings): void
    {
        $this->skipFirstRow = $settings['skipFirstRow'] ?? false;
        $this->sheetName = $settings['sheetName'] ?? 'Sheet1';
    }
}
