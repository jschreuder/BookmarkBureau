<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Config\MysqlDatabaseConfig;
use jschreuder\BookmarkBureau\Config\PostgresDatabaseConfig;
use jschreuder\BookmarkBureau\Config\SqliteDatabaseConfig;

$env = require __DIR__ . "/config/env.php";
$config = require __DIR__ . "/config/" . $env . ".php";

$databaseConfig = $config["databaseConfig"];

// Build Phinx configuration based on database type
$phinxConfig = match (true) {
    $databaseConfig instanceof SqliteDatabaseConfig => [
        "adapter" => "sqlite",
        "name" => str_replace("sqlite:", "", $databaseConfig->dsn),
        "suffix" => "", // Disable automatic suffix, use exact path from DSN
    ],
    $databaseConfig instanceof MysqlDatabaseConfig => [
        "adapter" => "mysql",
        "host" => $databaseConfig->host,
        "port" => $databaseConfig->port,
        "name" => $databaseConfig->dbname,
        "user" => $databaseConfig->user,
        "pass" => $databaseConfig->pass,
        "charset" => $databaseConfig->charset,
    ],
    $databaseConfig instanceof PostgresDatabaseConfig => [
        "adapter" => "pgsql",
        "host" => $databaseConfig->host,
        "port" => $databaseConfig->port,
        "name" => $databaseConfig->dbname,
        "user" => $databaseConfig->user,
        "pass" => $databaseConfig->pass,
        "charset" => $databaseConfig->charset,
    ],
    default => throw new RuntimeException(
        "Unsupported database config type: " . get_class($databaseConfig),
    ),
};

return [
    "paths" => [
        "migrations" => __DIR__ . "/migrations",
    ],
    "environments" => [
        "default_migration_table" => "phinxlog",
        "default_environment" => $env,
        $env => $phinxConfig,
    ],
];
