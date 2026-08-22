<?php

namespace OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\Simple;

use OpenDxp\Bundle\DataImporterBundle\Exception\InvalidConfigurationException;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Operator\AbstractOperator;
use OpenDxp\Bundle\DataImporterBundle\Mapping\Type\TransformationDataTypeService;
use OpenDxp\Bundle\DataImporterBundle\PimcoreDataImporterBundle;
use OpenDxp\Model\Element\ElementInterface;

class ObjectField extends AbstractOperator
{
    private string $attribute;

    private string $forwardParameter;

    public function setSettings(array $settings): void
    {
        // are there better defautls than empty string?
        $this->attribute = $settings['attribute'] ?? '';
        $this->forwardParameter = $settings['forward_parameter'] ?? '';
    }

    private function logWarning(string $logMessage): void
    {
        $this->applicationLogger->warning($logMessage . ' ', [
            'component' => PimcoreDataImporterBundle::LOGGER_COMPONENT_PREFIX . $this->configName,
        ]);
    }

    public function process(mixed $inputData, bool $dryRun = false): mixed
    {
        if (!$inputData instanceof ElementInterface) {
            $this->logWarning('Receveid a non ElementInterface to process.');

            return null;
        }

        if (!$this->attribute) {
            $this->logWarning('No attribute provided.');

            return null;
        }

        // better to pull full logic from ObjectFieldGetter / AnyGetter
        $getter = 'get' . ucfirst($this->attribute);

        if (!method_exists($inputData, $getter)) {
            $this->logWarning('Method ' .  $getter . ' not found on provided object.');

            return null;
        }

        if ($this->forwardParameter) {
            $value = $inputData->$getter($this->forwardParameter);
        } else {
            $value = $inputData->$getter();
        }

        // this expands paths
        if ($value instanceof ElementInterface) {
            $value = $value->getFullPath();
        }

        return $value;
    }

    /**
     *
     * @throws InvalidConfigurationException
     */
    public function evaluateReturnType(string $inputType, ?int $index = null): string
    {
        if ($inputType === TransformationDataTypeService::DATA_OBJECT) {
            // for numerics?
            return TransformationDataTypeService::DEFAULT_TYPE;
        } else {
            throw new InvalidConfigurationException(
                sprintf(
                    "Unsupported input type '%s' for load data object operator at transformation position %s",
                    $inputType,
                    $index
                )
            );
        }
    }
}
