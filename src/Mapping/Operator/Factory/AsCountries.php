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

use OpenDxp\Bundle\ApplicationLoggerBundle\ApplicationLogger;
use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\AbstractOperator;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Type\TransformationDataTypeService;
use OpenDxp\Localization\LocaleServiceInterface;

class AsCountries extends AbstractOperator
{
    public function __construct(ApplicationLogger $applicationLogger, private readonly LocaleServiceInterface $localeService)
    {
        parent::__construct($applicationLogger);
    }

    public function process(mixed $inputData, bool $dryRun = false): mixed
    {
        $countries = $this->localeService->getDisplayRegions();

        foreach ($inputData as &$input) {
            foreach ($countries as $countryCode => $country) {
                if (ltrim(rtrim((string) $input)) == $country) {
                    $input = $countryCode;

                    break;
                }
            }
        }

        return $inputData;
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function evaluateReturnType(string $inputType, ?int $index = null): string
    {
        if ($inputType != TransformationDataTypeService::DEFAULT_ARRAY) {
            throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for as countries operator at transformation position %s", $inputType, $index));
        }

        return TransformationDataTypeService::COUNTRY_ARRAY;
    }
}
