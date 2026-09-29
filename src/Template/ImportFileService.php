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

use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Model\Asset;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

/**
 * Файл импорта с asset-источником без захода в «Ресурсы»: шаблон для скачивания и загрузка
 * заполненного файла прямо в ассет, который читает импорт (`loaderConfig.settings.assetPath`).
 *
 * Шаблон — ассет «<имя> — шаблон.<расш>» рядом с рабочим файлом (там можно держать инструкцию
 * и оформление), иначе собирается из маппинга. Рабочий файл шаблоном не отдаётся: в нём строки
 * прошлой загрузки, и их легко залить повторно.
 *
 * Загрузка и запуск — разные действия («Загрузить файл», потом «Запустить»): если двое загрузят
 * файлы в один импорт между загрузкой и запуском, запустится последний загруженный. Это видно
 * в версиях ассета; блокировку ради такого случая не заводим.
 */
class ImportFileService
{
    public const TEMPLATE_SUFFIX = ' — шаблон';

    public function __construct(protected ImportTemplateBuilder $templateBuilder)
    {
    }

    public function supports(array $config): bool
    {
        return ($config['loaderConfig']['type'] ?? null) === 'asset'
            && trim((string) ($config['loaderConfig']['settings']['assetPath'] ?? '')) !== '';
    }

    public function assetPath(array $config): string
    {
        if (!$this->supports($config)) {
            throw new InvalidConfigurationException('Импорт читает файл не из ресурса (источник не «Ресурс»).');
        }

        return trim((string) $config['loaderConfig']['settings']['assetPath']);
    }

    public function templateAssetPath(string $assetPath): string
    {
        $dir = rtrim(dirname($assetPath), '/');
        $extension = pathinfo($assetPath, PATHINFO_EXTENSION);

        return sprintf(
            '%s/%s%s%s',
            $dir,
            pathinfo($assetPath, PATHINFO_FILENAME),
            self::TEMPLATE_SUFFIX,
            $extension !== '' ? '.' . $extension : ''
        );
    }

    /**
     * @return array{filename: string, content: string}
     */
    public function template(array $config): array
    {
        $assetPath = $this->assetPath($config);
        // Имя шаблона, а не рабочего файла — чтобы в загрузках их не путали
        $filename = basename($this->templateAssetPath($assetPath));

        $template = Asset::getByPath($this->templateAssetPath($assetPath));
        if ($template instanceof Asset && !$template instanceof Asset\Folder) {
            return ['filename' => $filename, 'content' => (string) $template->getData()];
        }

        if (($config['interpreterConfig']['type'] ?? null) !== 'xlsx') {
            throw new InvalidConfigurationException(sprintf(
                'Шаблона нет: положите его в ресурсы как «%s».',
                $this->templateAssetPath($assetPath)
            ));
        }

        return ['filename' => $filename, 'content' => $this->templateBuilder->buildFromConfig($config)];
    }

    /**
     * Проверяет файл до записи: неверное имя листа импорт иначе «проглатывает» — падает уже
     * в воркере, и человек видит только пустой результат.
     */
    public function validateUpload(array $config, string $filePath, string $originalName): void
    {
        $expected = strtolower(pathinfo($this->assetPath($config), PATHINFO_EXTENSION));
        $actual = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($expected !== '' && $actual !== $expected) {
            throw new InvalidConfigurationException(sprintf('Нужен файл .%s, загружен .%s.', $expected, $actual ?: '(без расширения)'));
        }

        if (($config['interpreterConfig']['type'] ?? null) !== 'xlsx') {
            return;
        }

        $sheetName = $this->templateBuilder->sheetName($config);
        try {
            $sheets = IOFactory::createReader('Xlsx')->listWorksheetNames($filePath);
        } catch (Throwable $e) {
            throw new InvalidConfigurationException('Файл не читается как xlsx: ' . $e->getMessage());
        }

        if (!in_array($sheetName, $sheets, true)) {
            throw new InvalidConfigurationException(sprintf(
                'В файле нет листа «%s» (есть: %s). Переименуйте лист или скачайте шаблон.',
                $sheetName,
                implode(', ', $sheets)
            ));
        }
    }

    /**
     * Кладёт содержимое в ассет импорта от имени пользователя — в истории версий ассета видно,
     * кто и когда загружал файл.
     */
    public function store(array $config, string $content, int $userId, string $originalName): Asset
    {
        $assetPath = $this->assetPath($config);
        $versionNote = 'Загружено через Data Importer: ' . $originalName;

        $asset = Asset::getByPath($assetPath);
        if ($asset instanceof Asset\Folder) {
            throw new InvalidConfigurationException('По пути файла импорта лежит папка: ' . $assetPath);
        }

        if ($asset instanceof Asset) {
            $asset->setData($content);
            $asset->setUserModification($userId);
            $asset->save(['versionNote' => $versionNote]);

            return $asset;
        }

        $folder = Asset\Service::createFolderByPath(rtrim(dirname($assetPath), '/'));
        $asset = Asset::create($folder->getId(), [
            'filename' => basename($assetPath),
            'data' => $content,
            'userOwner' => $userId,
            'userModification' => $userId,
        ], false);
        $asset->save(['versionNote' => $versionNote]);

        return $asset;
    }
}
