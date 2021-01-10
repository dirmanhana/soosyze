<?php

return [
    'node' => [
        'class' => 'SoosyzeCore\Node\Services\Node',
        'arguments' => ['@config', '@core', '@query', '@template']
    ],
    'nodeuser' => [
        'class' => 'SoosyzeCore\Node\Services\NodeUser',
        'arguments' => ['@alias', '@config', '@node.hook.user', '@query', '@router', '@user']
    ],
    'node.extend' => [
        'class' => 'SoosyzeCore\Node\Extend',
        'hooks' => [
            'install.user' => 'hookInstallUser',
            'install.menu' => 'hookInstallMenu',
            'uninstall.menu' => 'hookUninstallMenu'
        ]
    ],
    'node.hook.block' => [
        'class' => 'SoosyzeCore\Node\Hook\Block',
        'arguments' => ['@alias', '@node', '@query', '@router'],
        'hooks' => [
            'block.create.form.data' => 'hookCreateFormData',
            'block.node.next_previous' => 'hookBlockNextPrevious',
            'block.node.next_previous.edit.form' => 'hookNodeNextPreviousEditForm',
            'block.node.next_previous.update.validator' => 'hookNodeNextPreviousUpdateValidator',
            'block.node.next_previous.update.before' => 'hookNodeNextPreviousUpdateBefore'
        ]
    ],
    'node.hook.config' => [
        'class' => 'SoosyzeCore\Node\Hook\Config',
        'arguments' => ['@query'],
        'hooks' => [
            'config.edit.menu' => 'menu'
        ]
    ],
    'node.hook.filemanager' => [
        'class' => 'SoosyzeCore\Node\Hook\FileManager',
        'arguments' => ['@core', '@module', '@router'],
        'hooks' => [
            'node.create.form' => 'hookNodeCreateForm',
            'node.edit.form' => 'hookNodeEditForm',
            'entity.create.form' => 'hookEntityForm',
            'entity.edit.form' => 'hookEntityForm'
        ]
    ],
    'node.hook.app' => [
        'class' => 'SoosyzeCore\Node\Hook\App',
        'arguments' => ['@core'],
        'hooks' => [
            'app.response.after' => 'hookResponseAfter',
            'node.edit.response.after' => 'hookNodeEditResponseAfter'
        ]
    ],
    'node.hook.api.route' => [
        'class' => 'SoosyzeCore\Node\Hook\ApiRoute',
        'arguments' => ['@alias', '@query', '@router'],
        'hooks' => [
            'api.route' => 'hookApiRoute'
        ]
    ],
    'node.hook.url' => [
        'class' => 'SoosyzeCore\Node\Hook\Url',
        'arguments' => ['@alias', '@config', '@query', '@schema'],
        'hooks' => [
            'node.create.form.data' => 'hookCreateFormData',
            'node.create.form' => 'hookCreateForm',
            'node.store.validator' => 'hookStoreValidator',
            'node.store.after' => 'hookStoreAfter',
            'node.edit.form.data' => 'hookEditFormData',
            'node.edit.form' => 'hookCreateForm',
            'node.update.validator' => 'hookStoreValidator',
            'node.update.after' => 'hookUpdateValid',
            'node.delete.after' => 'hookDeleteValid'
        ]
    ],
    'node.hook.menu' => [
        'class' => 'SoosyzeCore\Node\Hook\Menu',
        'arguments' => ['@alias', '@query', '@schema'],
        'hooks' => [
            'node.create.form.data' => 'hookCreateFormData',
            'node.create.form' => 'hookCreateForm',
            'node.store.validator' => 'hookStoreValidator',
            'node.store.after' => 'hookStoreValid',
            'node.edit.form.data' => 'hookEditFormData',
            'node.edit.form' => 'hookCreateForm',
            'node.update.validator' => 'hookStoreValidator',
            'node.update.after' => 'hookUpdateValid',
            'node.delete.after' => 'hookDeleteValid',
            'menu.link.delete.after' => 'hookLinkDeleteValid'
        ]
    ],
    'node.hook.user' => [
        'class' => 'SoosyzeCore\Node\Hook\User',
        'arguments' => ['@query'],
        'hooks' => [
            'user.permission.module' => 'hookPermission',
            'route.node.admin' => 'hookNodeManager',
            'route.filter' => 'hookNodeManager',
            'route.filter.page' => 'hookNodeManager',
            'route.node.show' => 'hookNodeSow',
            'route.node.create' => 'hookNodeCreated',
            'route.node.clone' => 'hookNodeClone',
            'route.node.store' => 'hookNodeCreated',
            'route.node.edit' => 'hookNodeEdited',
            'route.node.update' => 'hookNodeEdited',
            'route.node.remove' => 'hookNodeDeleted',
            'route.node.delete' => 'hookNodeDeleted',
            'route.node.api.remove' => 'hookNodeDeleted',
            'route.node.api.delete' => 'hookNodeDeleted',
            'route.node.add' => 'hookNodeAdd',
            'route.entity.create' => 'hookNodeCreated',
            'route.entity.store' => 'hookNodeCreated',
            'route.entity.edit' => 'hookNodeEdited',
            'route.entity.update' => 'hookNodeEdited',
            'route.entity.delete' => 'hookNodeDeleted'
        ]
    ],
    'node.hook.nodeuser' => [
        'class' => 'SoosyzeCore\Node\Hook\NodeUser',
        'arguments' => ['@config', "@node", '@nodeuser', '@query', '@router', '@template', '@user'],
        'hooks' => [
            'user.show' => 'hookUserShow',
            'user.delete.after' => 'hookUserDeleteAfter'
        ]
    ],
    'node.hook.cron' => [
        'class' => 'SoosyzeCore\Node\Hook\Cron',
        'arguments' => ['@config', '@query'],
        'hooks' => [
            'app.cron' => 'hookCron'
        ]
    ]
];
