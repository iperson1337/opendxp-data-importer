<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace OpenDxp\Bundle\DataImporterBundle;

use OpenDxp\Bundle\DataImporterBundle\Migrations\Version20240715160305;
use OpenDxp\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use OpenDxp\Model\User\Permission;

class Installer extends SettingsStoreAwareInstaller
{
    const DATAHUB_ADAPTER_PERMISSION = 'plugin_datahub_adapter_dataImporterDataObject';

    public function needsReloadAfterInstall(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function install(): void
    {
        /**
         * The application logger can be deactivated. But it is necessary for the data-importer,
         * so we have to make sure that it is activated & installed.
         */
        $appLoggerInstaller = \OpenDxp::getContainer()->get(\OpenDxp\Bundle\ApplicationLoggerBundle\Installer::class);

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
        return Version20240715160305::class;
    }
}
