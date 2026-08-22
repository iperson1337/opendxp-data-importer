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

namespace OpenDxp\Bundle\DataImporterBundle\Mapping\Operator;

use OpenDxp\Bundle\DataImporterBundle\Settings\SettingsAwareInterface;

interface OperatorInterface extends SettingsAwareInterface
{
    /**
     * Apply transformation to input data
     *
     * @param mixed $inputData
     *
     * @return mixed
     */
    public function process($inputData, bool $dryRun = false);

    /**
     * Calculate resulting return type for given input type. Throw exception if input type not supported.
     */
    public function evaluateReturnType(string $inputType, ?int $index = null): string;

    /**
     * Generate string representation of given input
     *
     * @param mixed $inputData
     *
     * @return mixed
     */
    public function generateResultPreview($inputData);

    /**
     * Set name of current import configuration
     *
     *
     * @return mixed
     */
    public function setConfigName(string $configName);
}
