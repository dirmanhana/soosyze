<?php

namespace SoosyzeCore\Dashboard;

use Psr\Container\ContainerInterface;

class Extend extends \SoosyzeCore\System\ExtendModule
{
    public function getDir()
    {
        return __DIR__;
    }

    public function boot()
    {
        $this->loadTranslation('fr', __DIR__ . '/Lang/fr/main.json');
    }

    public function install(ContainerInterface $ci)
    {
    }

    public function seeders(ContainerInterface $ci)
    {
    }

    public function hookInstall(ContainerInterface $ci)
    {
        if ($ci->module()->has('Menu')) {
            $this->hookInstallMenu($ci);
        }
        if ($ci->module()->has('User')) {
            $this->hookInstallUser($ci);
        }
    }

    public function hookInstallMenu(ContainerInterface $ci)
    {
        $ci->query()
            ->insertInto('menu_link', [
                'key', 'icon', 'title_link', 'link', 'menu', 'weight', 'parent'
            ])
            ->values([
                'dashboard.index', 'fas fa-tachometer-alt', 'Dashboard', 'admin/dashboard',
                'menu-admin', 1, -1
            ])
            ->execute();
    }

    public function hookInstallUser(ContainerInterface $ci)
    {
        $ci->query()
            ->insertInto('role_permission', [ 'role_id', 'permission_id' ])
            ->values([ 3, 'dashboard.administer' ])
            ->execute();
    }

    public function uninstall(ContainerInterface $ci)
    {
    }

    public function hookUninstall(ContainerInterface $ci)
    {
        if ($ci->module()->has('Menu')) {
            $this->hookUninstallMenu($ci);
        }
        if ($ci->module()->has('User')) {
            $this->hookUninstallUser($ci);
        }
    }

    public function hookUninstallMenu(ContainerInterface $ci)
    {
        $ci->menu()->deleteLinks(static function () use ($ci) {
            return $ci->query()
                    ->from('menu_link')
                    ->where('key', 'like', 'dashboard%')
                    ->fetchAll();
        });
    }

    public function hookUninstallUser(ContainerInterface $ci)
    {
        $ci->query()
            ->from('role_permission')
            ->delete()
            ->where('permission_id', 'like', 'dashboard.%')
            ->execute();
    }
}
