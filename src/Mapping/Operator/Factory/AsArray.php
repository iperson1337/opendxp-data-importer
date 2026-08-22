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

namespace OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\Factory;

use OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\AbstractOperator;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Type\TransformationDataTypeService;

class AsArray extends AbstractOperator
{
    /**
     * @param mixed $inputData
     *
     * @return array|mixed
     */
    public function process($inputData, bool $dryRun = false)
    {
        if (!is_array($inputData)) {
            $inputData = [$inputData];
        }

        return $inputData;
    }

    public function evaluateReturnType(string $inputType, ?int $index = null): string
    {
        return TransformationDataTypeService::DEFAULT_ARRAY;
    }
}
