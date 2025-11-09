<?php

return [
    'categories' => [
        'overview' => [
            'title' => 'Overview',
            'icon' => 'fas fa-layer-group',
            'pages' => [
                'overview' => 'Tenant Overview',
            ],
        ],
        'manage_services' => [
            'title' => 'Manage Services',
            'icon' => 'fas fa-server',
            'pages' => [
                'players' => 'Players',
                'bans' => 'Bans',
                'blacklists' => 'Blacklists',
                'warnings' => 'Warnings',
                'logs' => 'Logs',
            ],
        ],
        'permissions' => [
            'title' => 'Permissions',
            'icon' => 'fas fa-user-shield',
            'pages' => [
                'permissions_overview' => 'Overview',
                'permissions_groups' => 'Groups',
                'permissions_group_permissions' => 'Group Permissions',
                'permissions_users' => 'Users',
            ],
        ],
        'tenant_admin' => [
            'title' => 'Tenant Admin',
            'icon' => 'fas fa-id-card',
            'pages' => [
                'contacts' => 'Key Contacts',
                'activity_logs' => 'Activity Logs',
            ],
        ],
    ],
];
