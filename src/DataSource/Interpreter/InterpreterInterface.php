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
use OpenDxp\Bundle\DataImporterBundle\Resolver\Resolver;
use OpenDxp\Bundle\DataImporterBundle\Settings\SettingsAwareInterface;

interface InterpreterInterface extends SettingsAwareInterface
{
    /**
     * Check if file is valid
     */
    public function fileValid(string $path, bool $originalFilename = false): bool;

    /**
     * Load file, create queue entries for importing and element cleanup
     */
    public function interpretFile(string $path): bool;

    /**
     * Read given file an extract preview data from it
     */
    public function previewData(string $path, int $recordNumber = 0, array $mappedColumns = []): PreviewData;

    /**
     * Set name of current import configuration
     */
    public function setConfigName(string $configName): void;

    /**
     * Set current execution type
     */
    public function setExecutionType(string $executionType): void;

    /**
     * Activate/Deactivate delta check
     */
    public function setDoDeltaCheck(bool $doDeltaCheck): void;

    /**
     * Set data index for id column in import data
     *
     * @param mixed $idDataIndex
     */
    public function setIdDataIndex($idDataIndex): void;

    /**
     * Activate/deactivate element cleanup
     */
    public function setDoCleanup(bool $doCleanup): void;

    /**
     * Activate/deactivate archivate import file, e.g. to application logger
     */
    public function setDoArchiveImportFile(bool $doArchiveImportFile): void;

    /**
     * Set resolver
     */
    public function setResolver(Resolver $resolver): void;
}
