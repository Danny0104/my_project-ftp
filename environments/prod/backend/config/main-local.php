<?php

return [
    'bootstrap' => [],
    'modules' => [],
    'components' => [
        'request' => [
            'cookieValidationKey' => getenv('BACKEND_COOKIE_KEY') ?: '',
        ],
    ],
];
