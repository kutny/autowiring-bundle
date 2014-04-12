<?php

namespace Kutny\AutowiringBundle\Compiler;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Symfony\Component\DependencyInjection\Reference;

class ParameterProcessor
{

    public function getParameterValue(ReflectionMethod $method, array $classes, $serviceId, ReflectionParameter $parameter)
    {
        $value = null;
        $resolved = false;

        // resolve by type hint
        if (!$resolved && ($parameterClass = $parameter->getClass())) {
            $value = $this->processParameterClass($parameterClass, $parameter, $classes);
            $resolved = true;
        }

        // resolve array by doc comment @param type
        if (!$resolved && $parameter->isArray()) {
            if ($docComment = $method->getDocComment()) {
                $paramAnnotationExists = preg_match(
                    "/@param\\s+(?<type>[A-Za-z0-9_]+)\\[\\]\\s+\\\${$parameter->getName()}/",
                    $docComment,
                    $paramAnnotation
                );

                if ($paramAnnotationExists) {
                    $uses = $this->extractUses($method->getDeclaringClass());
                    $parameterClass = $paramAnnotation["type"];
                    if (isset($uses[$parameterClass])) {
                        $parameterClass = $uses[$parameterClass];
                    }

                    $value = $this->processParameterClass(new ReflectionClass($parameterClass), $parameter, $classes, true);
                    $resolved = true;
                }
            }
        }

        // try default value
        if (!$resolved && $parameter->isDefaultValueAvailable()) {
            $value = $parameter->getDefaultValue();
            $resolved = true;
        }

        // exception if not resolved
        if (!$resolved) {
            throw new CannotResolveParameterException(
                'Class ' . $parameter->getDeclaringClass()->getName() .
                ' (service: ' . $serviceId . '), ' .
                'parameter $' . $parameter->getName()
            );
        }

        return $value;
    }

    private function extractUses(ReflectionClass $class)
    {
        $fileContents = file_get_contents($class->getFileName());
        $uses = array();

        if (preg_match_all(
            "/\\b[uU][sS][eE]\\s+((?<fqn>[A-Za-z0-9_\\\\]+)(?:\\s+[aA][sS]\\s+(?<alias>[A-Za-z0-9_\\\\]+))?)\\s*;/",
            $fileContents, $matches)
        ) {
            for ($i = 0, $l = count($matches["fqn"]); $i < $l; ++$i) {
                list($fqn, $alias) = array($matches["fqn"][$i], $matches["alias"][$i]);

                if (empty($alias)) {
                    $classPath = explode("\\", $fqn);
                    $alias = array_pop($classPath);
                }

                $uses[$alias] = $fqn;
            }
        }

        return $uses;
    }

    private function processParameterClass(ReflectionClass $parameterClass, ReflectionParameter $parameter, $classes, $allowMultiple = false)
    {
        $class = $parameterClass->getName();

        if (isset($classes[$class])) {
            if (count($classes[$class]) === 1) {
                $value = new Reference($classes[$class][0]);

            } elseif ($allowMultiple) {
                $value = array_map(function ($serviceId) {
                    return new Reference($serviceId);
                }, $classes[$class]);

            } else {
                $serviceNames = implode(', ', $classes[$class]);
                $message = 'Multiple services of ' . $class . ' defined (' . $serviceNames . '), class used in ' . $parameter->getDeclaringClass()->getName();

                throw new MultipleServicesOfClassException($message);
            }
        } else {
            if ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            } else {
                throw new ServiceNotFoundException('Service not found for ' . $class . ' used in ' . $parameter->getDeclaringClass()->getName());
            }
        }

        return $value;
    }
}
