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

namespace OpenDxp\Bundle\DataImporterBundle\DataSource\Loader;

use League\Flysystem\FilesystemOperator;
use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Helper\TemporaryFileHelperTrait;

class UploadLoader implements DataLoaderInterface
{
    use TemporaryFileHelperTrait;

    /**
     * @var string
     */
    protected $uploadFilePath;

    /**
     * @var string
     */
    protected $temporaryFile = null;

    public function __construct(protected FilesystemOperator $opendxpDataImporterUploadStorage)
    {
    }

    public function loadData(): string
    {
        if ($this->opendxpDataImporterUploadStorage->fileExists($this->uploadFilePath)) {
            $stream = $this->opendxpDataImporterUploadStorage->readStream($this->uploadFilePath);
            $this->temporaryFile = self::getTemporaryFileFromStream($stream, true);

            return $this->temporaryFile;
        }

        throw new InvalidConfigurationException('No file uploaded for import.');
    }

    public function setSettings(array $settings): void
    {
        $this->uploadFilePath = $settings['uploadFilePath'];
    }

    public function cleanup(): void
    {
        unlink($this->temporaryFile);
    }
}
