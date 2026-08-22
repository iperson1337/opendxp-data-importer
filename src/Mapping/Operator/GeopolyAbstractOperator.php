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

use OpenDxp\Model\DataObject\Data\GeoCoordinates;
use Override;

abstract class GeopolyAbstractOperator extends AbstractOperator
{
    /**
     * @param mixed $inputData
     *
     * @return array
     */
    public function process($inputData, bool $dryRun = false)
    {
        $data = [];
        $i = 0;
        if (is_array($inputData)) {
            foreach ($inputData as $input) {
                if (is_array($input)) {
                    $data[] = new GeoCoordinates($input[0], $input[1]);
                } else {
                    $coordinates[] = $input;
                    if (++$i % 2 === 0) {
                        $data[] = new GeoCoordinates($coordinates[0], $coordinates[1]);
                        $coordinates = null;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param mixed $inputData
     *
     * @return array
     */
    #[Override]
    public function generateResultPreview($inputData)
    {
        $preview = null;
        if (is_array($inputData)) {
            foreach ($inputData as $key => $item) {
                if ($item instanceof GeoCoordinates) {
                    $preview[$key] = 'Lat.: ' . $item->getLatitude() . ' Long.: ' . $item->getLongitude();
                }
            }
        }

        return $preview;
    }
}
