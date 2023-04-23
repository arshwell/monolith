<?php

namespace Arshwell\Monolith\Env;

use Arshwell\Monolith\Func;

final class EnvConfig
{
    /** @var array */
    private $configs;

    private $site = NULL;
    private $siteRoot = NULL;


    function __construct(array $config, array $envVariables)
    {
        $this->configs['databases'] = $config['databases'];
        $this->configs['development'] = $config['development'];
        $this->configs['filestorages'] = $config['filestorages'];
        $this->configs['services'] = $config['services'];
        $this->configs['web'] = $config['web'];

        // replace envVariables in the config
        array_walk_recursive($this->configs, function (&$value) use ($envVariables) {
            $value = preg_replace_callback(
                "~\%env\(([A-Z_]+?)\)\%~",
                function ($matches) use ($envVariables) {
                    return $envVariables[$matches[1]];
                },
                $value
            );
        });

        // NOTE: array_merge_recursive is not good
        $this->configs = array_replace_recursive(
            $this->configs,
            Func::arrayFlattenTree($this->configs, NULL, '.', true)
        );

        // Workaround: convert subarray ips into a key->value ips, in the "development.ips" key
        if (!empty($this->configs['development']['ips'])) {
            // NOTE: array_merge_recursive is not good
            $this->configs['development.ips'] = array_replace_recursive(
                $this->configs['development.ips'],
                Func::arrayFlattenTree($this->configs['development']['ips'], NULL, '.', true)
            );
        }

        $this->site = (strstr($this->configs['web.URL'], '/', true) ?: $this->configs['web.URL']);
        $this->siteRoot = (strstr($this->configs['web.URL'], '/') ?: '');
    }


    /**
     * @return mixed
     */
    function get(string $key)
    {
        return $this->configs[$key];
    }

    function getDbConnByIndex(int $index = 0): array
    {
        $conns = array_values($this->configs['databases']['conn']);

        try {
            return $conns[$index];
        }
        catch (\Exception $e) {
            throw new \Exception("|Arshwell| config/databases.json has only ". count($conns) ." connections.");
        }
    }

    function getFileStoragePath (string $fileStorageKey, string $folder, bool $append_folder = true): string {
        try {
            return $this->configs["filestorages.$fileStorageKey.paths.$folder"] .'/'. ($append_folder ? "$folder/" : '');
        }
        catch (\Exception $e) {
            throw new \Exception("|Arshwell| config/filestorages.json should contain [$folder] key, with string value. It represents the optional path to your $folder/ folder, or NULL for default path.");
        }
    }

    function getFileStoragePathsByIndex (int $fileStorageIndex = 0): array {
        $fileStorages = array_values($this->configs['filestorages']);

        try {
            return $fileStorages[$fileStorageIndex]['paths'];
        }
        catch (\Exception $e) {
            throw new \Exception("|Arshwell| config/filestorages.json has only ". count($fileStorages) ." file storages.");
        }
    }

    function getFileStoragePathByIndex (int $fileStorageIndex = 0, string $folder, bool $append_folder = true): string {
        $fileStorages = array_values($this->configs['filestorages']);

        if ($fileStorages[$fileStorageIndex]['paths'][$folder]) {
            $fileStorages[$fileStorageIndex]['paths'][$folder] .= '/';
        }

        try {
            return $fileStorages[$fileStorageIndex]['paths'][$folder] . ($append_folder ? "$folder/" : '');
        }
        catch (\Exception $e) {
            throw new \Exception("|Arshwell| config/filestorages.json has only ". count($fileStorages) ." file storages.");
        }
    }

    function getSite (): string {
        return $this->site;
    }

    function getSiteRoot (): string {
        return $this->siteRoot;
    }
}
