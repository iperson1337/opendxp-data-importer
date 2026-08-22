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
use OpenDxp\Model\Asset;

class AssetLoader implements DataLoaderInterface
{
    /**
     * @var string
     */
    protected $assetPath;

    /**
     * @var string
     */
    protected $temporaryFile = null;

    public function loadData(): string
    {
        $asset = Asset::getByPath($this->assetPath);
        if (empty($asset)) {
            throw new InvalidConfigurationException("Asset {$this->assetPath} not found.");
        }

        $this->temporaryFile = $asset->getTemporaryFile();

        return $this->temporaryFile;
    }

    public function setSettings(array $settings): void
    {
        if (empty($settings['assetPath'])) {
            throw new InvalidConfigurationException('Empty asset path.');
        }

        $this->assetPath = $settings['assetPath'];
    }

    public function cleanup(): void
    {
        unlink($this->temporaryFile);
    }
}
