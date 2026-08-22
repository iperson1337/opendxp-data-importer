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

use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Model\Element\ElementInterface;

class AttributeBasedStrategy implements PublishStrategyInterface
{
    /**
     * @var mixed
     */
    protected $dataSourceIndex;

    public function setSettings(array $settings): void
    {
        if (($dsi = $settings['dataSourceIndex'] ?? null) === null) {
            throw new InvalidConfigurationException('Empty data source index.');
        }

        $this->dataSourceIndex = $dsi;
    }

    public function updatePublishState(ElementInterface $element, bool $justCreated, array $inputData): ElementInterface
    {
        if (method_exists($element, 'setPublished')) {
            $element->setPublished($inputData[$this->dataSourceIndex] ?? false);
        }

        return $element;
    }
}
