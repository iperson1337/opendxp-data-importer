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

class PathStrategy extends AbstractLoad
{
    /**
     * @param string $identifier
     *
     * @throws InvalidConfigurationException
     */
    public function loadElementByIdentifier($identifier): ?ElementInterface
    {
        return $this->dataObjectLoader->loadByPath($identifier,
            $this->getClassName());
    }

    public function loadFullIdentifierList(): array
    {
        $sql = sprintf('SELECT CONCAT(`path`, `key`) FROM object_%s', $this->dataObjectClassId);

        return $this->db->fetchFirstColumn($sql);
    }
}
