<?php

return [
    'backupmanager.extend' => [
        'class' => 'SoosyzeCore\BackupManager\Extend',
        'hooks' => [
            'install.menu' => 'hookInstallMenu',
            'install.user' => 'hookInstallUser'
        ]
    ],
    'backupmanager' => [
        'class' => 'SoosyzeCore\BackupManager\Services\BackupManager',
        'arguments' => ['@config', '@core', '@router']
    ],
    'backupmanager.hook.config' => [
        'class' => 'SoosyzeCore\BackupManager\Hook\Config',
        'hooks' => [
            'config.edit.menu' => 'menu'
        ]
    ],
    'backupmanager.hook.user' => [
        'class' => 'SoosyzeCore\BackupManager\Hook\User',
        'hooks' => [
            'user.permission.module' => 'hookPermission',
            'route.backupmanager.admin' => 'hookBackupManage',
            'route.backupmanager.dobackup' => 'hookBackupManage',
            'route.backupmanager.delete' => 'hookBackupManage',
            'route.backupmanager.restore' => 'hookBackupManage',
            'route.backupmanager.delete.all' => 'hookBackupManage',
            'route.backupmanager.download' => 'hookBackupManage'
        ]
    ],
    'backupmanager.hook.cron' => [
        'class' => 'SoosyzeCore\BackupManager\Hook\Cron',
        'arguments' => ['@backupmanager', '@config'],
        'hooks' => [
            'app.cron' => 'hookAppCron'
        ]
    ]
];
