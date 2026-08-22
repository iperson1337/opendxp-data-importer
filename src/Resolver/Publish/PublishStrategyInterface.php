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

namespace OpenDxp\Bundle\DataImporterBundle\Resolver\Publish;

use OpenDxp\Bundle\DataImporterBundle\Settings\SettingsAwareInterface;
use OpenDxp\Model\Element\ElementInterface;

interface PublishStrategyInterface extends SettingsAwareInterface
{
    /**
     * Set publish state of element based on input data. Just created defines if element was creating during current run.
     */
    public function updatePublishState(ElementInterface $element, bool $justCreated, array $inputData): ElementInterface;
}
