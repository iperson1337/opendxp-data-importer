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

use Exception;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\AbstractOperator;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Type\TransformationDataTypeService;
use OpenDxp\Model\DataObject\Data\RgbaColor;
use Override;

class AsColor extends AbstractOperator
{
    /**
     * @throws Exception
     */
    public function process($inputData, bool $dryRun = false)
    {
        if (is_array($inputData)) {
            if (count($inputData) > 0 && is_numeric($inputData[0])) {
                return new RgbaColor(...$inputData);
            }
        } elseif (str_starts_with((string) $inputData, '#')) {
            $color = new RgbaColor();
            $color->setHex($inputData);

            return $color;
        }

        return new RgbaColor();
    }

    /**
     * @param mixed $inputData
     *
     * @return mixed|string
     */
    #[Override]
    public function generateResultPreview($inputData)
    {
        if ($inputData instanceof RgbaColor) {
            return $inputData->__toString();
        }

        return $inputData;
    }

    public function evaluateReturnType(string $inputType, ?int $index = null): string
    {
        return TransformationDataTypeService::RGBA_COLOR;
    }
}
