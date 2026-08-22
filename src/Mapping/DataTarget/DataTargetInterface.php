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

namespace OpenDxp\Bundle\DataImporterBundle\Mapping\DataTarget;

use OpenDxp\Bundle\DataImporterBundle\Settings\SettingsAwareInterface;
use OpenDxp\Model\Element\ElementInterface;

interface DataTargetInterface extends SettingsAwareInterface
{
    /**
     * Assign given data to element
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function assignData(ElementInterface $element, $data);
}
