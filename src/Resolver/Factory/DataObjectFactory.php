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

namespace OpenDxp\Bundle\DataImporterBundle\Resolver\Factory;

use Exception;
use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Factory;

class DataObjectFactory implements FactoryInterface
{
    /**
     * @var string
     */
    protected $subType;

    /**
     * @var Factory
     */
    protected $modelFactory;

    public function __construct(Factory $modelFactory)
    {
        $this->modelFactory = $modelFactory;
    }

    public function setSubType(string $subType): void
    {
        $this->subType = $subType;
    }

    /**
     * @throws InvalidConfigurationException
     * @throws Exception
     */
    public function createNewElement(): ElementInterface
    {
        $class = ClassDefinition::getById($this->subType);
        if (empty($class)) {
            throw new InvalidConfigurationException("Class `{$this->subType}` not found.");
        }

        $className = '\\OpenDxp\\Model\\DataObject\\' . ucfirst((string) $class->getName());
        $element = $this->modelFactory->build($className);

        if (!($element instanceof ElementInterface)) {
            throw new InvalidConfigurationException(
                "Object of class `{$this->subType}` could not be created."
            );
        }

        $element->setKey(uniqid('import-', true));

        return $element;
    }
}
