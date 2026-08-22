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

use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Bundle\DataImporterBundle\Processing\ImportProcessingService;
use OpenDxp\Bundle\DataImporterBundle\Resolver\Resolver;

class InterpreterFactory
{
    /**
     * @var InterpreterInterface[]
     */
    protected $interpreterBluePrints;

    /**
     * LoaderFactory constructor.
     *
     * @param InterpreterInterface[] $interpreterBluePrints
     */
    public function __construct(array $interpreterBluePrints)
    {
        $this->interpreterBluePrints = $interpreterBluePrints;
    }

    /**
     * @return InterpreterInterface
     *
     * @throws InvalidConfigurationException
     */
    public function loadInterpreter(string $configName, array $interpreterConfiguration, array $processingConfiguration, ?Resolver $resolver = null)
    {
        if (empty($interpreterConfiguration['type']) || !array_key_exists($interpreterConfiguration['type'], $this->interpreterBluePrints)) {
            throw new InvalidConfigurationException('Unknown loader type `' . ($interpreterConfiguration['type'] ?? '') . '`');
        }

        $loader = clone $this->interpreterBluePrints[$interpreterConfiguration['type']];
        $loader->setConfigName($configName);
        $loader->setExecutionType($processingConfiguration['executionType'] ?? ImportProcessingService::EXECUTION_TYPE_SEQUENTIAL);
        $loader->setIdDataIndex($processingConfiguration['idDataIndex'] ?? null);
        $loader->setDoDeltaCheck($processingConfiguration['doDeltaCheck'] ?? false);
        $loader->setDoCleanup($processingConfiguration['cleanup']['doCleanup'] ?? false);
        $loader->setDoArchiveImportFile($processingConfiguration['doArchiveImportFile'] ?? false);

        if ($resolver) {
            $loader->setResolver($resolver);
        }

        $loader->setSettings($interpreterConfiguration['settings'] ?? []);

        return $loader;
    }
}
