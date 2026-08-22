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

namespace OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\Factory;

use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\AbstractOperator;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Type\TransformationDataTypeService;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Data\Hotspotimage;
use OpenDxp\Model\DataObject\Data\ImageGallery;

class Gallery extends AbstractOperator
{
    /**
     * @param mixed $inputData
     * @param bool $dryRun
     *
     * @return ImageGallery
     */
    public function process($inputData, bool $dryRun = false)
    {
        $items = [];

        if (!is_array($inputData)) {
            $inputData = [$inputData];
        }

        foreach ($inputData as $asset) {
            if ($asset instanceof Asset\Image) {
                $hotspotImage = new Hotspotimage($asset);
                $items[] = $hotspotImage;
            }
        }

        return new ImageGallery($items);
    }

    /**
     * @param string $inputType
     * @param int|null $index
     *
     * @return string
     *
     * @throws InvalidConfigurationException
     */
    public function evaluateReturnType(string $inputType, int $index = null): string
    {
        if (!in_array($inputType, [TransformationDataTypeService::ASSET, TransformationDataTypeService::ASSET_ARRAY])) {
            throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for gallery operator at transformation position %s", $inputType, $index));
        }

        return TransformationDataTypeService::GALLERY;
    }

    /**
     * @param mixed $inputData
     *
     * @return array|mixed
     */
    public function generateResultPreview($inputData)
    {
        if ($inputData instanceof ImageGallery) {
            $items = [];

            foreach ($inputData->getItems() as $item) {
                $items[] = 'GalleryImage: ' . ($item->getImage() ? $item->getImage()->getFullPath() : '');
            }

            return $items;
        }

        return $inputData;
    }
}
