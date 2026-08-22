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

use OpenDxp\Bundle\DataImporterBundle\Settings\SettingsAwareInterface;

interface DataLoaderInterface extends SettingsAwareInterface
{
    /**
     * Load data from source, eventually create a temporary file somewhere
     * and return the path to the data
     *
     * @return string path to the data
     */
    public function loadData(): string;

    /**
     * Cleanup temporary file if necessary
     */
    public function cleanup(): void;
}
