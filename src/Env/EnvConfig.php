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

    function getDbConnByIndex(int $index = 0): ?array
    {
        $conns = array_values($this->configs['databases']['conn']);

        return $conns[$index] ?? null;
    }

    function getDbConnNameByIndex(int $index = 0): ?string
    {
        $conns = array_values($this->configs['databases']['conn']);

        return $conns[$index]['name'] ?? null;
    }

    function getFileStoragePath (string $fileStorageKey, string $folder, bool $append_folder = true): ?string {
        if (!isset($this->configs["filestorages.$fileStorageKey.paths.$folder"])) {
            return null;
        }

        return rtrim($this->configs["filestorages.$fileStorageKey.paths.$folder"], '/') .'/'. ($append_folder ? "$folder/" : '');
    }

    function getFileStoragePathsByIndex (int $fileStorageIndex = 0): ?array {
        $fileStorages = array_values($this->configs['filestorages']);

        if (!isset($fileStorages[$fileStorageIndex])) {
            return null;
        }

        try {
            return $fileStorages[$fileStorageIndex]['paths'];
        }
        catch (\Exception $e) {
            throw new \Exception("|Arshwell| config/filestorages.json misses 'paths' key for fileStorageIndex: {$fileStorageIndex}.");
        }
    }

    function getFileStoragePathByIndex (int $fileStorageIndex = 0, string $folder, bool $append_folder = true): ?string {
        $fileStorages = array_values($this->configs['filestorages']);

        if (!isset($fileStorages[$fileStorageIndex]['paths'][$folder])) {
            return null;
        }

        if ($fileStorages[$fileStorageIndex]['paths'][$folder]) {
            $fileStorages[$fileStorageIndex]['paths'][$folder] .= '/';
        }

        return $fileStorages[$fileStorageIndex]['paths'][$folder] . ($append_folder ? "$folder/" : '');
    }

    function getSite (): string {
        return $this->site;
    }

    function getSiteRoot (): string {
        return $this->siteRoot;
    }
}
