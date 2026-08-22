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

namespace OpenDxp\Bundle\DataImporterBundle\Resolver\Load;

use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Model\Element\ElementInterface;
use Override;

class AttributeStrategy extends AbstractLoad
{
    /**
     * @var string
     */
    protected $attributeName;

    /**
     * @var string
     */
    protected $attributeLanguage;

    /**
     * @var bool
     */
    protected $includeUnpublished;

    /**
     * @throws InvalidConfigurationException
     */
    #[Override]
    public function setSettings(array $settings): void
    {
        parent::setSettings($settings);

        if (empty($settings['attributeName'])) {
            throw new InvalidConfigurationException('Empty attribute name.');
        }

        $this->attributeName = $settings['attributeName'];
        $this->attributeLanguage = $settings['language'] ?? null;
        $this->includeUnpublished = $settings['includeUnpublished'] ?? false;
    }

    /**
     * @param string $identifier
     *
     * @throws InvalidConfigurationException
     */
    public function loadElementByIdentifier($identifier): ?ElementInterface
    {
        return $this->dataObjectLoader->loadByAttribute($this->getClassName(),
            $this->attributeName,
            $identifier,
            $this->attributeLanguage,
            $this->includeUnpublished,
            1);
    }

    public function loadFullIdentifierList(): array
    {
        $tableName = 'object_' . $this->dataObjectClassId;
        if ($this->attributeLanguage) {
            $tableName = 'object_localized_' . $this->dataObjectClassId . '_' . $this->attributeLanguage;
        }

        $sql = sprintf('SELECT `%s` FROM %s', $this->attributeName, $tableName);

        return $this->db->fetchFirstColumn($sql);
    }
}
