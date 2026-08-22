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

namespace OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\Factory;

use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\AbstractOperator;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Type\TransformationDataTypeService;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Data\Hotspotimage;
use OpenDxp\Model\DataObject\Data\ImageGallery;
use Override;

class Gallery extends AbstractOperator
{
    /**
     * @param mixed $inputData
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
     * @throws InvalidConfigurationException
     */
    public function evaluateReturnType(string $inputType, ?int $index = null): string
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
    #[Override]
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
