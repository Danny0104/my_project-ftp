<?php

use common\components\ErrorHandler;

return [
    'errorHandler' => [
        'class' => ErrorHandler::class,
        'categories' => [
            'application' => 'Application Errors',
            'database' => 'Database Errors',
            'security' => 'Security Events',
            'performance' => 'Performance Issues',
            'user' => 'User Actions',
            'system' => 'System Events',
        ],
        'severityLevels' => [
            'emergency' => 0,
            'alert' => 1,
            'critical' => 2,
            'error' => 3,
            'warning' => 4,
            'notice' => 5,
            'info' => 6,
            'debug' => 7,
        ],
    ],
];
