<?php

namespace Arshwell\Monolith\Env;

use Arshwell\Monolith\Func;

final class EnvConfig
{
    /** @var array */
    private $configs;

    private $site = NULL;
    private $root = NULL;


    function __construct(array $config)
    {
        $this->configs['databases'] = $config['databases'];
        $this->configs['development'] = $config['development'];
        $this->configs['locations'] = $config['locations'];
        $this->configs['services'] = $config['services'];
        $this->configs['web'] = $config['web'];

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
        $this->root = (strstr($this->configs['web.URL'], '/') ?: '');
    }


    /**
     * @return mixed
     */
    function get(string $key)
    {
        return $this->configs[$key];
    }

    function getLocationPath (string $folder, bool $append_folder = true): string {
        try {
            return $this->configs["locations.$folder"] .'/'. ($append_folder ? "$folder/" : '');
        }
        catch (\Exception $e) {
            throw new \Exception("|Arshwell| config/locations.json should contain [$folder] key, with string value. It represents the optional path to your $folder/ folder, or NULL for default path.");
        }
    }

    function getSite (): string {
        return $this->site;
    }

    function getRoot (): string {
        return $this->root;
    }
}
