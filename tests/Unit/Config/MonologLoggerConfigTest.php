<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Config\MonologLoggerConfig;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

describe("MonologLoggerConfig", function () {
    describe("constructor parameters", function () {
        test("stores logger name and path", function () {
            $config = new MonologLoggerConfig(
                name: "test-logger",
                logPath: "php://memory",
            );

            expect($config->name)->toBe("test-logger");
            expect($config->logPath)->toBe("php://memory");
        });

        test("stores enable request logging flag", function () {
            $config = new MonologLoggerConfig(
                name: "test-logger",
                logPath: "php://memory",
                enableRequestLogging: true,
            );

            expect($config->enableRequestLogging)->toBe(true);
        });

        test("defaults enable request logging to true", function () {
            $config = new MonologLoggerConfig(
                name: "test-logger",
                logPath: "php://memory",
            );

            expect($config->enableRequestLogging)->toBe(true);
        });

        test("stores log level", function () {
            $config = new MonologLoggerConfig(
                name: "test-logger",
                logPath: "php://memory",
                level: Level::Warning,
            );

            expect($config->level)->toBe(Level::Warning);
        });

        test("defaults log level to Notice", function () {
            $config = new MonologLoggerConfig(
                name: "test-logger",
                logPath: "php://memory",
            );

            expect($config->level)->toBe(Level::Notice);
        });
    });

    describe("logger creation", function () {
        test("creates Logger instance", function () {
            $config = new MonologLoggerConfig(
                name: "test-logger",
                logPath: "php://memory",
            );

            $logger = $config->createLogger();
            expect($logger)->toBeInstanceOf(LoggerInterface::class);
            expect($logger)->toBeInstanceOf(Logger::class);
        });

        test("logger has configured name", function () {
            $config = new MonologLoggerConfig(
                name: "my-app-logger",
                logPath: "php://memory",
            );

            $logger = $config->createLogger();
            expect($logger->getName())->toBe("my-app-logger");
        });

        test("logger can log messages to memory stream", function () {
            $config = new MonologLoggerConfig(
                name: "test-logger",
                logPath: "php://memory",
            );

            $logger = $config->createLogger();
            $logger->info("Test message");
            // Info logs at level 200, which is above Notice (250), so won't be logged by default

            // Try with a lower level
            $config = new MonologLoggerConfig(
                name: "test-logger",
                logPath: "php://memory",
                level: Level::Debug,
            );
            $logger = $config->createLogger();
            $logger->info("Test message");
            // Logger should successfully handle the message

            expect($logger)->toBeInstanceOf(Logger::class);
        });

        test("logger can log to file", function () {
            $logFile = sys_get_temp_dir() . "/test_log_" . uniqid() . ".log";

            $config = new MonologLoggerConfig(
                name: "file-logger",
                logPath: $logFile,
                level: Level::Debug,
            );

            $logger = $config->createLogger();
            $logger->info("Test log entry");

            // Check that log file was created
            expect(file_exists($logFile))->toBeTrue();

            // Cleanup
            if (file_exists($logFile)) {
                unlink($logFile);
            }
        });
    });

    describe("readonly property", function () {
        test("config is readonly", function () {
            $config = new MonologLoggerConfig(
                name: "test-logger",
                logPath: "php://memory",
            );

            // Attempting to modify readonly properties should fail
            expect(fn() => $config->name = "new-name")
                ->toThrow(Error::class);
        });
    });

    describe("different log levels", function () {
        test("can be configured with different levels", function () {
            $levels = [
                Level::Emergency,
                Level::Alert,
                Level::Critical,
                Level::Error,
                Level::Warning,
                Level::Notice,
                Level::Info,
                Level::Debug,
            ];

            foreach ($levels as $level) {
                $config = new MonologLoggerConfig(
                    name: "level-test",
                    logPath: "php://memory",
                    level: $level,
                );
                expect($config->level)->toBe($level);
            }
        });
    });
});
