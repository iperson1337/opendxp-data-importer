<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace OpenDxp\Bundle\DataImporterBundle\Resolver\Location;

use Exception;
use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Bundle\DataImporterBundle\Tool\DataObjectLoader;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Folder;
use OpenDxp\Model\Element\ElementInterface;

class FindParentAndCreateFolderStrategy implements LocationStrategyInterface
{
    const string FIND_BY_ID = 'id';
    const string FIND_BY_PATH = 'path';
    const string FIND_BY_ATTRIBUTE = 'attribute';

    /**
     * @var mixed
     */
    protected mixed $dataSourceIndex;

    /**
     * @var string
     */
    protected string $findStrategy;

    /**
     * @var string
     */
    protected string $fallbackPath;

    /**
     * @var mixed
     */
    protected mixed $attributeDataObjectClassId;

    /**
     * @var string
     */
    protected string $attributeName;

    /**
     * @var string
     */
    protected string $attributeLanguage;

    /**
     * @var string
     */
    protected string $folderName;

    public function __construct(
        protected DataObjectLoader $dataObjectLoader,
    ) {
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function setSettings(array $settings): void
    {
        if ($settings['dataSourceIndex'] !== 0 && $settings['dataSourceIndex'] !== '0' && empty($settings['dataSourceIndex'])) {
            throw new InvalidConfigurationException('Empty data source index.');
        }

        $this->dataSourceIndex = $settings['dataSourceIndex'];

        $this->fallbackPath = $settings['fallbackPath'] ?? null;

        if (empty($settings['findStrategy'])) {
            throw new InvalidConfigurationException('Empty find strategy.');
        }

        $this->findStrategy = $settings['findStrategy'];

        if (empty($settings['folderName'])) {
            throw new InvalidConfigurationException('Empty folder name.');
        }

        $this->folderName = $settings['folderName'];

        if ($this->findStrategy === self::FIND_BY_ATTRIBUTE) {
            if (empty($settings['attributeDataObjectClassId'])) {
                throw new InvalidConfigurationException('Empty data object class for attribute loading.');
            }

            $this->attributeDataObjectClassId = $settings['attributeDataObjectClassId'];

            if (empty($settings['attributeName'])) {
                throw new InvalidConfigurationException('Empty data attribute name.');
            }

            $this->attributeName = $settings['attributeName'];
            $this->attributeLanguage = $settings['attributeLanguage'] ?? null;
        }
    }

    /**
     * @throws InvalidConfigurationException
     * @throws Exception
     */
    public function updateParent(ElementInterface $element, array $inputData): ElementInterface
    {
        $parentObject = null;

        $identifier = $inputData[$this->dataSourceIndex] ?? null;

        if (isset($identifier)) {
            switch ($this->findStrategy) {
                case self::FIND_BY_ID:
                    $parentObject = $this->dataObjectLoader->loadById($identifier);
                    break;
                case self::FIND_BY_PATH:
                    $parentObject = $this->dataObjectLoader->loadByPath($identifier);
                    break;
                case self::FIND_BY_ATTRIBUTE:
                    $class = ClassDefinition::getById($this->attributeDataObjectClassId);
                    if ($class === null) {
                        throw new InvalidConfigurationException("Class `{$this->attributeDataObjectClassId}` not found.");
                    }
                    $className = '\\OpenDxp\\Model\\DataObject\\' . ucfirst($class->getName());
                    $parentObject = $this->dataObjectLoader->loadByAttribute(
                        $className,
                        $this->attributeName,
                        $identifier,
                        $this->attributeLanguage,
                        true,
                        1
                    );
                    break;
            }
        }

        if (!($parentObject instanceof DataObject) && $this->fallbackPath) {
            $parentObject = DataObject::getByPath($this->fallbackPath);
        }

        if ($parentObject instanceof AbstractObject) {
            try {
                // Create a folder within the parent object
                $folder = $this->createChildFolderOnObject(
                    $parentObject,
                    $this->folderName
                );

                return $element->setParent($folder);
            } catch (Exception $e) {
                // If there's an error creating the folder, fall back to using the parent object directly
                return $element->setParent($parentObject);
            }
        }

        return $element;
    }

    /**
     * Creates a child folder under this object
     *
     * @param AbstractObject $object The parent object
     * @param string $folderName The name of the folder to create
     * @return Folder The created or existing folder
     * @throws \Exception If there's an error creating the folder
     */
    public function createChildFolderOnObject(AbstractObject $object, string $folderName): Folder
    {
        $folderName = trim($folderName, '/');

        $parentPath = $object->getFullPath();

        $folderPath = $parentPath . ($parentPath === '/' ? '' : '/') . $folderName;

        $existingFolder = Folder::getByPath($folderPath);

        if ($existingFolder instanceof Folder) {
            return $existingFolder;
        }

        $folder = new Folder();
        $folder->setKey($folderName);
        $folder->setParent($object);
        $folder->save();

        return $folder;
    }
}
