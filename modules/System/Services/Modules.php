<?php

namespace System\Services;

use Soosyze\Components\Util\Util;

/* Folder Module CMS */
define("DEFAULT_MODULES_PATH", 'app' . DS . 'modules');
/* Folder Module Core */
define("ADMIN_MODULES_PATH", 'modules');

class Modules
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * Si le module est installé.
     *
     * @param string $name Nom du module.
     *
     * @return array
     */
    public function isInstall($name)
    {
        return $this->query
                ->from('module')
                ->where('name', $name)
                ->fetch();
    }

    /**
     * Si le module est requis par le module virtuel "core".
     *
     * @param string $key Nom du module.
     *
     * @return array
     */
    public function isRequiredCore($key)
    {
        return $this->query
                ->from('module_required')
                ->where('name_module', $key)
                ->where('name_required', 'Core')
                ->lists('name_required');
    }

    /**
     * Si le module est requis par un autre module installé.
     *
     * @param string $key Nom du module.
     *
     * @return array
     */
    public function isRequiredForModule($key)
    {
        return $this->query
                ->from('module')
                ->leftJoin('module_required', 'name', 'module_required.name_required')
                ->where('name', $key)
                ->isNotNull('name_module')
                ->lists('name_module');
    }

    public function listModuleActive(array $columns = [])
    {
        $moduleKey = [];
        $modules   = $this->query
            ->select($columns)
            ->from('module')
            ->fetchAll();
        foreach ($modules as $value) {
            $moduleKey[ $value[ 'name' ] ] = $value;
        }

        return $moduleKey;
    }

    public function listModuleActiveNotRequire(array $columns = [])
    {
        return $this->query
                ->select($columns)
                ->from('module')
                ->leftJoin('module_required', 'name', 'module_required.name_required')
                ->isNull('name_module')
                ->lists('name');
    }

    /**
     * Désinstalle un module.
     *
     * @param string $name Nom du module.
     */
    public function uninstallModule($name)
    {
        $this->query
            ->from('module')
            ->delete()
            ->where('name', $name)
            ->execute();

        $this->query
            ->from('module_required')
            ->delete()
            ->where('name_module', $name)
            ->execute();
    }

    /**
     * Installe un module.
     *
     * @param array $value Données du module.
     */
    public function installModule($value)
    {
        $required = $value[ 'required' ];
        unset($value[ 'required' ]);

        $this->query
            ->insertInto('module', [ 'name', 'controller', 'version', 'description',
                'package', 'locked' ])
            ->values($value)
            ->execute();

        foreach ($required as $require) {
            $this->query
                ->insertInto('module_required', [ 'name_module', 'name_required' ])
                ->values([ $value[ 'name' ], $require ])
                ->execute();
        }
    }

    public function getConfig($nameModule)
    {
        $config = $this->getConfigAll();

        return $config[ $nameModule ];
    }

    public function getModuleAll()
    {
        return array_merge($this->getModules(), $this->getModulesCore());
    }

    public function getModules($dir = DEFAULT_MODULES_PATH)
    {
        return Util::getFolder($dir);
    }

    public function getModulesCore()
    {
        return $this->getModules(ADMIN_MODULES_PATH);
    }

    public function getConfigModule($dir = DEFAULT_MODULES_PATH)
    {
        $config  = [];
        $modules = $this->getModules($dir);

        foreach ($modules as $module) {
            $file = $dir . DS . $module . DS . 'config.json';
            if (file_exists($file)) {
                $tmp    = Util::getJson($file);
                $config = array_merge($config, $tmp);
            }
        }

        return $config;
    }

    public function getConfigModuleCore()
    {
        return $this->getConfigModule(ADMIN_MODULES_PATH);
    }

    public function getConfigAll()
    {
        return array_merge($this->getConfigModule(), $this->getConfigModuleCore());
    }
}
