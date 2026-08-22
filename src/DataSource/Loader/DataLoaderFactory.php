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

use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;

class DataLoaderFactory
{
    /**
     * @var DataLoaderInterface[]
     */
    protected $dataLoaderBluePrints;

    /**
     * DataLoaderFactory constructor.
     *
     * @param DataLoaderInterface[] $dataLoaderBluePrints
     */
    public function __construct(array $dataLoaderBluePrints)
    {
        $this->dataLoaderBluePrints = $dataLoaderBluePrints;
    }

    /**
     * @return DataLoaderInterface
     *
     * @throws InvalidConfigurationException
     */
    public function loadDataLoader(array $configuration)
    {
        if (empty($configuration['type']) || !array_key_exists($configuration['type'], $this->dataLoaderBluePrints)) {
            throw new InvalidConfigurationException('Unknown loader type `' . ($configuration['type'] ?? '') . '`');
        }

        $dataLoader = clone $this->dataLoaderBluePrints[$configuration['type']];
        $dataLoader->setSettings($configuration['settings'] ?? []);

        return $dataLoader;
    }
}
