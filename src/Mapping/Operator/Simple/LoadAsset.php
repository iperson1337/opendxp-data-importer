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
use OpenDxp\Bundle\DataImporterBundle\OpenDxpDataImporterBundle;
use OpenDxp\Model\Asset;
use Override;

class LoadAsset extends ImportAsset
{
    const LOAD_STRATEGY_ID = 'id';

    const LOAD_STRATEGY_PATH = 'path';

    /**
     * @var string
     */
    protected $loadStrategy;

    #[Override]
    public function setSettings(array $settings): void
    {
        $this->loadStrategy = $settings['loadStrategy'] ?? self::LOAD_STRATEGY_PATH;
    }

    /**
     * @param mixed $inputData
     *
     * @return array|false|mixed|null
     *
     * @throws InvalidConfigurationException
     */
    #[Override]
    public function process($inputData, bool $dryRun = false)
    {
        $returnScalar = false;
        if (!is_array($inputData)) {
            $returnScalar = true;
            $inputData = [$inputData];
        }

        $assets = [];

        foreach ($inputData as $data) {
            $asset = null;
            $cleanData = trim((string) $data);
            if ($this->loadStrategy === self::LOAD_STRATEGY_PATH) {
                $asset = Asset::getByPath($cleanData);
            } elseif ($this->loadStrategy === self::LOAD_STRATEGY_ID) {
                if (is_numeric($cleanData)) {
                    $asset = Asset::getById((int)$cleanData);
                }
            } else {
                throw new InvalidConfigurationException("Unknown load strategy '{ $this->loadStrategy }'");
            }

            if ($asset instanceof Asset) {
                $assets[] = $asset;
            } elseif (!$dryRun && !empty($data)) {
                $this->applicationLogger->warning("Could not load asset from `$data` ", [
                    'component' => OpenDxpDataImporterBundle::LOGGER_COMPONENT_PREFIX . $this->configName,
                ]);
            }
        }

        if ($returnScalar) {
            if (!empty($assets)) {
                return reset($assets);
            }

            return null;
        } else {
            return $assets;
        }
    }
}
