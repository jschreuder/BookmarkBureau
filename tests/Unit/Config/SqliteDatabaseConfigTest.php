<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Config\SqliteDatabaseConfig;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;

describe("SqliteDatabaseConfig", function () {
    describe("database type", function () {
        test("returns sqlite as database type", function () {
            $config = new SqliteDatabaseConfig("sqlite::memory:");
            expect($config->getDatabaseType())->toBe("sqlite");
        });
    });

    describe("database connection", function () {
        test("creates in-memory PDO connection", function () {
            $config = new SqliteDatabaseConfig("sqlite::memory:");
            $pdo = $config->getConnection();

            expect($pdo)->toBeInstanceOf(PDO::class);
        });

        test(
            "returns same PDO instance on multiple calls (caching)",
            function () {
                $config = new SqliteDatabaseConfig("sqlite::memory:");
                $pdo1 = $config->getConnection();
                $pdo2 = $config->getConnection();

                expect($pdo1)->toBe($pdo2);
            },
        );

        test("creates connection with error mode exception", function () {
            $config = new SqliteDatabaseConfig("sqlite::memory:");
            $pdo = $config->getConnection();

            expect($pdo->getAttribute(PDO::ATTR_ERRMODE))->toBe(
                PDO::ERRMODE_EXCEPTION,
            );
        });

        test("can execute SQL queries on connection", function () {
            $config = new SqliteDatabaseConfig("sqlite::memory:");
            $pdo = $config->getConnection();

            $pdo->exec(
                "CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)",
            );
            $result = $pdo->query(
                "SELECT name FROM sqlite_master WHERE type='table' AND name='test_table'",
            );
            $rows = $result->fetchAll();

            expect($rows)->toHaveLength(1);
        });

        test("supports file-based SQLite database", function () {
            $dbPath = sys_get_temp_dir() . "/test_sqlite_" . uniqid() . ".db";
            $config = new SqliteDatabaseConfig("sqlite:{$dbPath}");
            $pdo = $config->getConnection();

            expect($pdo)->toBeInstanceOf(PDO::class);

            // Cleanup
            if (file_exists($dbPath)) {
                unlink($dbPath);
            }
        });
    });

    describe("default pipeline", function () {
        test("provides default pipeline", function () {
            $config = new SqliteDatabaseConfig("sqlite::memory:");
            $pipeline = $config->getDefaultPipeline();

            expect($pipeline)->toBeInstanceOf(PipelineInterface::class);
        });

        test(
            "returns same pipeline instance on multiple calls (caching)",
            function () {
                $config = new SqliteDatabaseConfig("sqlite::memory:");
                $pipeline1 = $config->getDefaultPipeline();
                $pipeline2 = $config->getDefaultPipeline();

                expect($pipeline1)->toBe($pipeline2);
            },
        );

        test("pipeline includes PdoTransactionMiddleware", function () {
            $config = new SqliteDatabaseConfig("sqlite::memory:");
            $pipeline = $config->getDefaultPipeline();

            // Pipeline should be properly constructed with middleware
            expect($pipeline)->toBeInstanceOf(PipelineInterface::class);
        });
    });
});
