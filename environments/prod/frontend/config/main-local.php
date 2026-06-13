<?php

return [
    'bootstrap' => [],
    'modules' => [],
    'components' => [
        'request' => [
            'cookieValidationKey' => getenv('FRONTEND_COOKIE_KEY') ?: '',
        ],
    ],
];
