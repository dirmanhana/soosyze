<?php

return [
    'config.hook.user' => [
        'class' => 'SoosyzeCore\Config\Hook\User',
        'arguments' => ['@core'],
        'hooks' => [
            'user.permission.module' => 'hookPermission',
            'route.config.admin' => 'hookConfigAdmin',
            'route.config.edit' => 'hookConfigManage',
            'route.config.update' => 'hookConfigManage'
        ]
    ],
    'config.extend' => [
        'class' => 'SoosyzeCore\Config\Extend',
        'hooks' => [
            'install.user' => 'hookInstallUser',
            'install.menu' => 'hookInstallMenu'
        ]
    ]
];
