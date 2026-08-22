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

namespace OpenDxp\Bundle\DataImporterBundle\Settings;

use Exception;
use OpenDxp\Bundle\DataHubBundle\Configuration\Dao;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ConfigurationPreparationService
{
    /**
     * @param string|array|null $currentConfig
     * @param bool $ignorePermissions
     *
     * @return array
     *
     * @throws Exception
     */
    public function prepareConfiguration(string $configName, $currentConfig = null, $ignorePermissions = false)
    {
        if ($currentConfig) {
            if (is_string($currentConfig)) {
                $currentConfig = json_decode($currentConfig, true);
            }
            $config = $currentConfig;
        } else {
            $configuration = Dao::getByName($configName);
            if (!$configuration) {
                throw new Exception('Configuration ' . $configName . ' does not exist.');
            }

            $config = $configuration->getConfiguration();
            if (!$ignorePermissions) {
                if (!$configuration->isAllowed('read')) {
                    throw new AccessDeniedHttpException('Access denied');
                }

                $config['userPermissions'] = [
                    'update' => $configuration->isAllowed('update'),
                    'delete' => $configuration->isAllowed('delete'),
                ];
            }
        }

        //init config array with default values
        $config = array_merge([
            'loaderConfig' => [],
            'interpreterConfig' => [],
            'resolverConfig' => [
                'loadingStrategy' => [],
                'createLocationStrategy' => [],
                'locationUpdateStrategy' => [],
                'publishingStrategy' => [],
            ],
            'processingConfig' => [],
            'mappingConfig' => [],
            'executionConfig' => [],
        ], $config);

        return $config;
    }
}
