<?php

return [
    'components' => [
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // API routes
                'api/auth/login' => 'api/auth/login',
                'api/auth/signup' => 'api/auth/signup',
                'api/auth/logout' => 'api/auth/logout',
                'api/auth/profile' => 'api/auth/profile',
                'api/auth/update-profile' => 'api/auth/update-profile',
                'api/auth/refresh-token' => 'api/auth/refresh-token',
                
                'api/positions' => 'api/position/index',
                'api/positions/<id:\d+>' => 'api/position/view',
                'api/positions/<id:\d+>/apply' => 'api/position/apply',
                'api/positions/create' => 'api/position/create',
                
                'api/applications' => 'api/application/index',
                'api/applications/<id:\d+>' => 'api/application/view',
                'api/applications/<id:\d+>/withdraw' => 'api/application/withdraw',
                'api/applications/<id:\d+>/approve' => 'api/application/approve',
                'api/applications/<id:\d+>/reject' => 'api/application/reject',
                
                'api/notifications' => 'api/notification/index',
                'api/notifications/<id:\d+>/read' => 'api/notification/mark-read',
                'api/notifications/<id:\d+>/unread' => 'api/notification/mark-unread',
                'api/notifications/<id:\d+>/delete' => 'api/notification/delete',
                
                'api/dashboard' => 'api/dashboard/index',
                'api/stats' => 'api/dashboard/stats',
            ],
        ],
    ],
];
