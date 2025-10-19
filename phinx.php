<?php declare(strict_types = 1);

$env = require __DIR__ . '/config/env.php';
$db = require __DIR__ . '/config/' . $env . '.php';

preg_match(
    '#^(?P<type>[a-z]+):.*(?:host=(?P<host>[^;]+)).*?(?:;port=(?P<port>[^;]+))?.*$#uiD',
    $db['db.dsn'],
    $matches
);

return [
    'paths' => [
        'migrations' => __DIR__ . '/migrations',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_database' => $env,
        $env => [
            'adapter' => $matches['type'],
            'host' => $matches['host'],
            'name' => $db['db.dbname'],
            'user' => $db['db.user'],
            'pass' => $db['db.pass'],
            'port' => $matches['port'] ?? 3306,
            'charset' => 'utf8',
        ],
    ]
];
