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

namespace OpenDxp\Bundle\DataImporterBundle\Resolver\Location;

use OpenDxp\Bundle\DataImporterBundle\Settings\SettingsAwareInterface;
use OpenDxp\Model\Element\ElementInterface;

interface LocationStrategyInterface extends SettingsAwareInterface
{
    /**
     * Update parent of given element based on input data
     */
    public function updateParent(ElementInterface $element, array $inputData): ElementInterface;
}
