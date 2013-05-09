<?php

namespace Kutny\AutowiringBundle\Compiler;

use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClassListBuilder
{

    /**
     * @SuppressWarnings(PMD.NPathComplexity)
     * @SuppressWarnings(PMD.CyclomaticComplexity)
     * @SuppressWarnings(PMD.ExcessiveMethodLength)
     */
    public function buildClassList(ContainerBuilder $containerBuilder)
    {
        $classes = array();
        $parameterBag = $containerBuilder->getParameterBag();

        foreach ($containerBuilder->getDefinitions() as $id => $definition) {
            if (!$definition->isPublic()) {
                continue;
            }

            // to prevent processing of some Doctrine cached classes
            if ($definition->getFile() !== null) {
                continue;
            }

            $class = $parameterBag->resolveValue($definition->getClass());

            if ($class === null) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection) {
                $classes[$reflection->getName()][] = $id;

                foreach ($reflection->getInterfaceNames() as $interface) {
                    $classes[$interface][] = $id;
                }

                $parent = $reflection;

                while (($parent = $parent->getParentClass())) {
                    $classes[$parent->getName()][] = $id;
                }
            }
        }

        $classes += $this->getSymfonyFrameworkServices();

        return $classes;
    }

    private function getSymfonyFrameworkServices()
    {
        $classes = array();

        $classes['Symfony\Bundle\TwigBundle\TwigEngine'][] = 'templating';
        $classes['Symfony\Bundle\FrameworkBundle\Routing\Router'][] = 'router';
        $classes['Symfony\Bundle\FrameworkBundle\HttpKernel'][] = 'http_kernel';
        $classes['Symfony\Component\Form\FormFactory'][] = 'form.factory';
        $classes['Doctrine\Bundle\DoctrineBundle\Registry'][] = 'doctrine';
        $classes['Symfony\Component\Security\Core\SecurityContext'][] = 'security.context';

        return $classes;
    }
}
