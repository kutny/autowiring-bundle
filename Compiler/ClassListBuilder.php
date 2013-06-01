<?php

namespace Kutny\AutowiringBundle\Compiler;

use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClassListBuilder
{

    public function buildClassList(ContainerBuilder $containerBuilder)
    {
        $classes = array();
        $parameterBag = $containerBuilder->getParameterBag();

        foreach ($containerBuilder->getDefinitions() as $serviceId => $definition) {
            $class = $parameterBag->resolveValue($definition->getClass());

            if ($class === null) {
                continue;
            }

            $this->processClass($serviceId, $class, $classes);
        }

        return $classes;
    }

    private function processClass($serviceId, $class, array &$classes)
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection) {
            return;
        }

        $classes[$reflection->getName()][] = $serviceId;

        foreach ($reflection->getInterfaceNames() as $interface) {
            $classes[$interface][] = $serviceId;
        }

        $parent = $reflection;

        while (($parent = $parent->getParentClass())) {
            $classes[$parent->getName()][] = $serviceId;
        }
    }
}
