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

use OpenDxp\Bundle\ApplicationLoggerBundle\ApplicationLogger;

abstract class AbstractOperator implements OperatorInterface
{
    /**
     * @var string
     */
    protected $configName;

    /**
     * @var ApplicationLogger
     */
    protected $applicationLogger;

    /**
     * AbstractOperator constructor.
     */
    public function __construct(ApplicationLogger $applicationLogger)
    {
        $this->applicationLogger = $applicationLogger;
    }

    /**
     * @return void
     */
    public function setConfigName(string $configName)
    {
        $this->configName = $configName;
    }

    /**
     * @param mixed $inputData
     *
     * @return mixed
     */
    public function generateResultPreview($inputData)
    {
        return $inputData;
    }

    public function setSettings(array $settings): void
    {
        //nothing to do
    }
}
