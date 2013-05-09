<?php

namespace Kutny\AutowiringBundle;

use Kutny\AutowiringBundle\YamlConfigLoader\ConfigFileIsEmptyException;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

class YamlConfigLoader extends FileLoader
{

    protected static $loading = array();

    public function load($fileName, $type = null)
    {
        $filePath = $this->locator->locate($fileName);

        $data = file_get_contents($filePath);

        if (null === $data) {
            throw new ConfigFileIsEmptyException($filePath);
        }

        $configDefinitions = Yaml::parse($data);

        $configDefinitions = $this->parseImports($configDefinitions, $filePath);

        return $configDefinitions;
    }

    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'yml' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    private function parseImports(array $configDefinitions, $filePath)
    {
        if (!isset($configDefinitions['imports'])) {
            return $configDefinitions;
        }

        foreach ($configDefinitions['imports'] as $import) {
            $this->setCurrentDir(dirname($filePath));

            $configDefinitionsFromImport = $this->import(
                $import['resource'],
                null,
                isset($import['ignore_errors']) ? (Boolean)$import['ignore_errors'] : false,
                $filePath
            );

            $configDefinitions = array_merge_recursive($configDefinitions, $configDefinitionsFromImport);
        }

        return $configDefinitions;
    }

}
