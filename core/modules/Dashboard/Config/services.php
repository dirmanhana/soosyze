<?php

return [
    'dashboard' => [
        'class' => 'SoosyzeCore\Dashboard\Services\Dashboard',
        'arguments' => ['@config', '@core']
    ],
    'dashboard.extend' => [
        'class' => 'SoosyzeCore\Dashboard\Extend',
        'hooks' => [
            'install.user' => 'hookInstallUser',
            'install.menu' => 'hookInstallMenu'
        ]
    ],
    'dashboard.hook.user' => [
        'class' => 'SoosyzeCore\Dashboard\Hook\User',
        'hooks' => [
            'user.permission.module' => 'hookPermission',
            'route.dashboard.index' => 'hookDashboardAdminister',
            'route.dashboard.info' => 'hookDashboardAdminister',
            'route.dashboard.cron' => 'hookDashboardAdminister',
            'route.dashboard.trans' => 'hookDashboardAdminister'
        ]
    ]
];
