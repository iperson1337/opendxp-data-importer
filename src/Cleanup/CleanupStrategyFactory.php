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

namespace OpenDxp\Bundle\DataImporterBundle\Cleanup;

use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;

class CleanupStrategyFactory
{
    /**
     * @var CleanupStrategyInterface[]
     */
    protected $cleanupStrategies;

    /**
     * CleanupStrategyFactory constructor.
     *
     * @param CleanupStrategyInterface[] $cleanupStrategies
     */
    public function __construct(array $cleanupStrategies)
    {
        $this->cleanupStrategies = $cleanupStrategies;
    }

    /**
     * @return CleanupStrategyInterface
     *
     * @throws InvalidConfigurationException
     */
    public function loadCleanupStrategy(string $type)
    {
        if (empty($type) || !array_key_exists($type, $this->cleanupStrategies)) {
            throw new InvalidConfigurationException('Unknown loader type `' . $type . '`');
        }

        return $this->cleanupStrategies[$type];
    }
}
