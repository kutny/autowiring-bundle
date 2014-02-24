<?php

namespace Kutny\AutowiringBundle\Compiler;

use ReflectionMethod;
use Symfony\Component\DependencyInjection\Definition;

class ClassConstructorFiller
{

    private $parameterProcessor;

    public function __construct(ParameterProcessor $parameterProcessor)
    {
        $this->parameterProcessor = $parameterProcessor;
    }

    public function autowireParams(ReflectionMethod $constructor, $serviceId, Definition $definition, array $classes)
    {
        $explicitlyDefinedArguments = $definition->getArguments();
        $allArguments = array();

        foreach ($constructor->getParameters() as $index => $parameter) {
            if (array_key_exists($index, $explicitlyDefinedArguments)) {
                $allArguments[] = $explicitlyDefinedArguments[$index];
            } else if ($parameter->isDefaultValueAvailable()) {
                $allArguments[] = $parameter->getDefaultValue();
            } else {
                $allArguments[] = $this->parameterProcessor->getParameterValue($parameter, $classes, $serviceId);
            }
        }

        $definition->setArguments($allArguments);
    }
}
