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

namespace OpenDxp\Bundle\DataImporterBundle;

use Exception;
use OpenDxp;
use OpenDxp\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use OpenDxp\Model\User\Permission;
use Override;

class Installer extends SettingsStoreAwareInstaller
{
    const DATAHUB_ADAPTER_PERMISSION = 'plugin_datahub_adapter_dataImporterDataObject';

    #[Override]
    public function needsReloadAfterInstall(): bool
    {
        return true;
    }

    /**
     * @throws Exception
     */
    #[Override]
    public function install(): void
    {
        $appLoggerInstaller = OpenDxp::getContainer()->get(\OpenDxp\Bundle\ApplicationLoggerBundle\Installer::class);

        if (!$appLoggerInstaller->isInstalled()) {
            $appLoggerInstaller->install();
        }

        // create backend permission
        Permission\Definition::create(self::DATAHUB_ADAPTER_PERMISSION)
            ->setCategory(\OpenDxp\Bundle\DataHubBundle\Installer::DATAHUB_PERMISSION_CATEGORY)
            ->save();

        parent::install();
    }

    public function getLastMigrationVersionClassName(): ?string
    {
        return null;
    }
}
