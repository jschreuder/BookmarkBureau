<?php declare(strict_types = 1);

$env = require __DIR__ . '/config/env.php';
$db = require __DIR__ . '/config/' . $env . '.php';

$dsn = $db['db.dsn'];

// Extract database type first
if (!preg_match('#^(?P<type>[a-z]+):#i', $dsn, $typeMatch)) {
    throw new RuntimeException('Invalid DSN format: unable to determine database type');
}

$type = strtolower($typeMatch['type']);
$config = [
    'name' => $db['db.dbname'],
    'user' => $db['db.user'] ?? null,
    'pass' => $db['db.pass'] ?? null,
    'charset' => 'utf8',
];

// Parse DSN based on type
switch ($type) {
    case 'mysql':
    case 'pgsql':
        // Parse host-based DSN: mysql:host=localhost;port=3306;dbname=test
        if (preg_match(
            '#host=(?P<host>[^;]+)(?:;port=(?P<port>\d+))?#i',
            $dsn,
            $matches
        )) {
            $config['adapter'] = $type === 'pgsql' ? 'pgsql' : 'mysql';
            $config['host'] = $matches['host'];
            $config['port'] = isset($matches['port']) 
                ? (int)$matches['port'] 
                : ($type === 'pgsql' ? 5432 : 3306);
        } else {
            throw new RuntimeException("Invalid $type DSN format: host not found");
        }
        break;

    case 'sqlite':
        // Parse SQLite DSN: sqlite:/path/to/database.db or sqlite::memory:
        if (preg_match('#^sqlite:(?P<path>.+)$#i', $dsn, $matches)) {
            $config['adapter'] = 'sqlite';
            $config['name'] = $matches['path'];
            // SQLite doesn't need user/pass/host/port
            unset($config['user'], $config['pass'], $config['charset']);
        } else {
            throw new RuntimeException('Invalid SQLite DSN format');
        }
        break;

    default:
        throw new RuntimeException("Unsupported database type: $type. Supported types: mysql, pgsql, sqlite");
}

return [
    'paths' => [
        'migrations' => __DIR__ . '/migrations',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => $env,
        $env => $config,
    ]
];