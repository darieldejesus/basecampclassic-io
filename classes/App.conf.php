<?php
$appSettings = [
    'settings' => [
        // Slim Settings
        'determineRouteBeforeAppMiddleware' => false,
        'displayErrorDetails' => true,
        'db' => [
            'driver'    => 'mysql',
            'host'      => '127.0.0.1',
            'database'  => 'basecampio',
            'username'  => 'root',
            'password'  => 'welc0me',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]
    ],
];

return $appSettings;