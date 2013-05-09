<?php

namespace Kutny\AutowiringBundle;

use Kutny\AutowiringBundle\Compiler\ClassConstructorFiller;
use Kutny\AutowiringBundle\Compiler\ClassListBuilder;
use ReflectionClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

class AutowiringCompilerPass implements CompilerPassInterface
{

    private $classConstructorFiller;
    private $classListBuilder;

    public function __construct(ClassConstructorFiller $classConstructorFiller, ClassListBuilder $classListBuilder)
    {
        $this->classConstructorFiller = $classConstructorFiller;
        $this->classListBuilder = $classListBuilder;
    }

    public function process(ContainerBuilder $containerBuilder)
    {
        $classList = $this->classListBuilder->buildClassList($containerBuilder);

        $servicesForAutoloading = $this->getServicesForAutowiring($containerBuilder);
        $parameterBag = $containerBuilder->getParameterBag();

        foreach ($containerBuilder->getDefinitions() as $serviceId => $definition) {
            if (!in_array($serviceId, $servicesForAutoloading)) {
                continue;
            }

            if (!$definition->isPublic()) {
                continue;
            }

            if ($definition->getFactoryClass() || $definition->getFactoryMethod()) {
                continue;
            }

            $class = $parameterBag->resolveValue($definition->getClass());

            if ($class === null) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            $constructor = $reflection->getConstructor();

            if ($constructor !== null && $constructor->isPublic()) {
                $this->classConstructorFiller->autowireParams($constructor, $definition, $classList);
            }
        }
    }

    private function getServicesForAutowiring(ContainerBuilder $containerBuilder)
    {
        $kernelRootDir = $containerBuilder->getParameter('kernel.root_dir');
        $symfonyEnvironment = $containerBuilder->getParameter('kernel.environment');
        $configDirectories = array($kernelRootDir . '/config');

        $pathToConfigFile = $kernelRootDir . '/config/config_' . $symfonyEnvironment . '.yml';

        $fileLocator = new FileLocator($configDirectories);
        $configLoader = new YamlConfigLoader($fileLocator);
        $serviceDefinitions = $configLoader->load($pathToConfigFile);

        if (!array_key_exists('services', $serviceDefinitions) || !is_array($serviceDefinitions['services'])) {
            throw new AutowiringException('Services not defined in Symfony config');
        }

        $serviceIds = array_keys($serviceDefinitions['services']);

        return $serviceIds;
    }
}
