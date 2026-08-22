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

namespace OpenDxp\Bundle\DataImporterBundle\Preview;

use Exception;
use League\Flysystem\FilesystemOperator;
use OpenDxp\Bundle\DataHubBundle\Configuration\Dao;
use OpenDxp\Helper\TemporaryFileHelperTrait;
use OpenDxp\Model\User;

class PreviewService
{
    use TemporaryFileHelperTrait;

    public function __construct(protected FilesystemOperator $opendxpDataImporterPreviewStorage)
    {
    }

    public function writePreviewFile(string $configName, string $sourcePath, User $user)
    {
        $target = $this->getPreviewFilePath($configName, $user);
        $this->opendxpDataImporterPreviewStorage->write($target, file_get_contents($sourcePath));
    }

    /**
     * @throws Exception
     */
    protected function getPreviewFilePath(string $configName, User $user): string
    {
        $configuration = Dao::getByName($configName);
        if (!$configuration) {
            throw new Exception('Configuration ' . $configName . ' does not exist.');
        }

        $filePath = $configuration->getName() . '/' . $user->getId() . '.import';

        return $filePath;
    }

    public function getLocalPreviewFile(string $configName, User $user): ?string
    {
        $filePath = $this->getPreviewFilePath($configName, $user);

        if ($this->opendxpDataImporterPreviewStorage->fileExists($filePath)) {
            $stream = $this->opendxpDataImporterPreviewStorage->readStream($filePath);

            return self::getLocalFileFromStream($stream);
        }

        return null;
    }
}
