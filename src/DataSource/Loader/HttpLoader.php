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
use Symfony\Component\Filesystem\Filesystem;

class HttpLoader implements DataLoaderInterface
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $schema;

    /**
     * @var string
     */
    protected $importFilePath;

    public function __construct(
        protected Filesystem $filesystem
    ) {
    }

    public function loadData(): string
    {
        $folder = OPENDXP_PRIVATE_VAR . '/tmp/datahub/dataimporter/http-loader/';
        $this->filesystem->mkdir($folder, 0775);

        $this->importFilePath = $folder . uniqid('http-import-');
        $fullUrl = $this->schema . $this->url;

        if (copy($fullUrl, $this->importFilePath)) {
            return $this->importFilePath;
        } else {
            throw new InvalidConfigurationException(sprintf('Could not copy from remote location `%s` to local tmp file `%s`', $fullUrl, $this->importFilePath));
        }
    }

    public function cleanup(): void
    {
        unlink($this->importFilePath);
    }

    public function setSettings(array $settings): void
    {
        if (empty($settings['url'])) {
            throw new InvalidConfigurationException('Empty URL.');
        }
        $this->url = $settings['url'];

        if (empty($settings['schema'])) {
            throw new InvalidConfigurationException('Empty Schema.');
        }
        $this->schema = $settings['schema'];
    }
}
