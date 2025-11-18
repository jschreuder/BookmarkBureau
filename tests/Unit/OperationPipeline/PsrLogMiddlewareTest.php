<?php

use jschreuder\BookmarkBureau\OperationPipeline\PsrLogMiddleware;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

describe("PsrLogMiddleware", function () {
    describe("initialization", function () {
        test("creates middleware with required parameters", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "TestOperation",
            );

            expect($middleware)->toBeInstanceOf(PsrLogMiddleware::class);
        });

        test("creates middleware with custom log level", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "TestOperation",
                logLevel: LogLevel::INFO,
            );

            expect($middleware)->toBeInstanceOf(PsrLogMiddleware::class);
        });

        test("uses DEBUG as default log level", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger
                ->shouldReceive("log")
                ->with(LogLevel::DEBUG, Mockery::any())
                ->twice();

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "TestOperation",
            );
            $testObject = new stdClass();
            $middleware->process($testObject, fn($data) => $data);
        });
    });

    describe("process method", function () {
        test("logs operation start with object type", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger
                ->shouldReceive("log")
                ->with(
                    LogLevel::DEBUG,
                    "TestOperation started with object of type stdClass",
                )
                ->once()
                ->ordered();
            $logger
                ->shouldReceive("log")
                ->with(LogLevel::DEBUG, Mockery::any())
                ->once()
                ->ordered();

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "TestOperation",
            );
            $testObject = new stdClass();
            $middleware->process($testObject, fn($data) => $data);
        });

        test("logs operation completion with return object type", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger
                ->shouldReceive("log")
                ->with(LogLevel::DEBUG, Mockery::any())
                ->once()
                ->ordered();
            $logger
                ->shouldReceive("log")
                ->with(
                    LogLevel::DEBUG,
                    "TestOperation completed with object of type stdClass",
                )
                ->once()
                ->ordered();

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "TestOperation",
            );
            $testObject = new stdClass();
            $middleware->process($testObject, fn($data) => $data);
        });

        test("returns result from next callable", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger->shouldReceive("log")->twice();

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "TestOperation",
            );
            $inputObject = new stdClass();
            $expectedResult = new stdClass();
            $expectedResult->result = "success";

            $result = $middleware->process(
                $inputObject,
                fn($data) => $expectedResult,
            );

            expect($result)->toBe($expectedResult);
        });

        test("calls next callable with input data", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger->shouldReceive("log")->twice();

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "TestOperation",
            );
            $inputObject = new stdClass();
            $inputObject->value = 42;
            $receivedData = null;

            $middleware->process($inputObject, function ($data) use (
                &$receivedData,
            ) {
                $receivedData = $data;
                return $data;
            });

            expect($receivedData)->toBe($inputObject);
            expect($receivedData->value)->toBe(42);
        });

        test("logs with custom log level", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger
                ->shouldReceive("log")
                ->with(LogLevel::INFO, Mockery::any())
                ->twice();

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "TestOperation",
                logLevel: LogLevel::INFO,
            );
            $testObject = new stdClass();
            $middleware->process($testObject, fn($data) => $data);
        });

        test("logs different operation names", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger
                ->shouldReceive("log")
                ->with(
                    LogLevel::DEBUG,
                    "CreateDashboard started with object of type stdClass",
                )
                ->once()
                ->ordered();
            $logger
                ->shouldReceive("log")
                ->with(
                    LogLevel::DEBUG,
                    "CreateDashboard completed with object of type stdClass",
                )
                ->once()
                ->ordered();

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "CreateDashboard",
            );
            $testObject = new stdClass();
            $middleware->process($testObject, fn($data) => $data);
        });

        test("logs with different input and output object types", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger
                ->shouldReceive("log")
                ->with(
                    LogLevel::DEBUG,
                    "Transform started with object of type stdClass",
                )
                ->once()
                ->ordered();
            $logger
                ->shouldReceive("log")
                ->with(
                    LogLevel::DEBUG,
                    "Transform completed with object of type ArrayIterator",
                )
                ->once()
                ->ordered();

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "Transform",
            );
            $inputObject = new stdClass();
            $outputObject = new ArrayIterator([]);

            $middleware->process($inputObject, fn($data) => $outputObject);
        });

        test("handles exceptions from next callable", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger
                ->shouldReceive("log")
                ->with(
                    LogLevel::DEBUG,
                    "FailingOperation started with object of type stdClass",
                )
                ->once();

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "FailingOperation",
            );
            $testObject = new stdClass();

            expect(
                fn() => $middleware->process(
                    $testObject,
                    fn($data) => throw new Exception("Operation failed"),
                ),
            )->toThrow(Exception::class, "Operation failed");
        });

        test("does not log completion when exception is thrown", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger
                ->shouldReceive("log")
                ->with(
                    LogLevel::WARNING,
                    "FailingOperation started with object of type stdClass",
                )
                ->once();

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "FailingOperation",
                logLevel: LogLevel::WARNING,
            );
            $testObject = new stdClass();

            try {
                $middleware->process(
                    $testObject,
                    fn($data) => throw new Exception("Operation failed"),
                );
            } catch (Exception) {
                // Expected
            }
        });

        test("maintains data integrity through middleware", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger->shouldReceive("log")->twice();

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "ProcessData",
            );
            $inputData = new stdClass();
            $inputData->name = "Test";
            $inputData->count = 42;

            $result = $middleware->process($inputData, function ($data) {
                $data->processed = true;
                return $data;
            });

            expect($result->name)->toBe("Test");
            expect($result->count)->toBe(42);
            expect($result->processed)->toBeTrue();
        });
    });

    describe("nullable data handling", function () {
        test("handles null data as input", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger
                ->shouldReceive("log")
                ->with(
                    LogLevel::DEBUG,
                    "TestOperation started with object of type null",
                )
                ->once()
                ->ordered();
            $logger
                ->shouldReceive("log")
                ->with(
                    LogLevel::DEBUG,
                    "TestOperation completed with object of type null",
                )
                ->once()
                ->ordered();

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "TestOperation",
            );
            $result = $middleware->process(null, fn($data) => $data);

            expect($result)->toBeNull();
        });

        test("returns null from next callable", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger->shouldReceive("log")->twice();

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "TestOperation",
            );
            $inputObject = new stdClass();

            $result = $middleware->process($inputObject, fn($data) => null);

            expect($result)->toBeNull();
        });

        test("logs null as type when next callable returns null", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger
                ->shouldReceive("log")
                ->with(
                    LogLevel::DEBUG,
                    "ConvertToNull started with object of type stdClass",
                )
                ->once()
                ->ordered();
            $logger
                ->shouldReceive("log")
                ->with(
                    LogLevel::DEBUG,
                    "ConvertToNull completed with object of type null",
                )
                ->once()
                ->ordered();

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "ConvertToNull",
            );
            $testObject = new stdClass();
            $middleware->process($testObject, fn($data) => null);
        });

        test("handles null data passed to next callable", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger->shouldReceive("log")->twice();

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "ProcessNull",
            );
            $receivedData = "not set";

            $middleware->process(null, function ($data) use (&$receivedData) {
                $receivedData = $data;
                return $data;
            });

            expect($receivedData)->toBeNull();
        });

        test(
            "logs correct types for null input and object output",
            function () {
                $logger = Mockery::mock(LoggerInterface::class);
                $logger
                    ->shouldReceive("log")
                    ->with(
                        LogLevel::DEBUG,
                        "Create started with object of type null",
                    )
                    ->once()
                    ->ordered();
                $logger
                    ->shouldReceive("log")
                    ->with(
                        LogLevel::DEBUG,
                        "Create completed with object of type stdClass",
                    )
                    ->once()
                    ->ordered();

                $middleware = new PsrLogMiddleware(
                    logger: $logger,
                    operationName: "Create",
                );
                $newObject = new stdClass();
                $middleware->process(null, fn($data) => $newObject);
            },
        );

        test(
            "logs correct types for object input and null output",
            function () {
                $logger = Mockery::mock(LoggerInterface::class);
                $logger
                    ->shouldReceive("log")
                    ->with(
                        LogLevel::DEBUG,
                        "Delete started with object of type stdClass",
                    )
                    ->once()
                    ->ordered();
                $logger
                    ->shouldReceive("log")
                    ->with(
                        LogLevel::DEBUG,
                        "Delete completed with object of type null",
                    )
                    ->once()
                    ->ordered();

                $middleware = new PsrLogMiddleware(
                    logger: $logger,
                    operationName: "Delete",
                );
                $testObject = new stdClass();
                $middleware->process($testObject, fn($data) => null);
            },
        );
    });

    describe("PipelineMiddlewareInterface implementation", function () {
        test("implements PipelineMiddlewareInterface", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "Test",
            );

            expect($middleware)->toBeInstanceOf(
                jschreuder\BookmarkBureau\OperationPipeline\PipelineMiddlewareInterface::class,
            );
        });

        test("process method has correct signature", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger->shouldReceive("log")->twice();

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "Test",
            );
            $input = new stdClass();

            $output = $middleware->process($input, fn($data) => $data);

            expect($output)->toBeInstanceOf(stdClass::class);
        });

        test("process method accepts null data", function () {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger->shouldReceive("log")->twice();

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "Test",
            );

            $output = $middleware->process(null, fn($data) => $data);

            expect($output)->toBeNull();
        });
    });

    describe("logging message format", function () {
        test(
            "start message includes operation name and object class",
            function () {
                $capturedMessages = [];
                $logger = Mockery::mock(LoggerInterface::class);
                $logger
                    ->shouldReceive("log")
                    ->andReturnUsing(function ($level, $message) use (
                        &$capturedMessages,
                    ) {
                        $capturedMessages[] = $message;
                    });

                $middleware = new PsrLogMiddleware(
                    logger: $logger,
                    operationName: "MyOperation",
                );
                $input = new stdClass();
                $middleware->process($input, fn($data) => $data);

                expect($capturedMessages[0])->toBe(
                    "MyOperation started with object of type stdClass",
                );
            },
        );

        test(
            "completion message includes operation name and result class",
            function () {
                $capturedMessages = [];
                $logger = Mockery::mock(LoggerInterface::class);
                $logger
                    ->shouldReceive("log")
                    ->andReturnUsing(function ($level, $message) use (
                        &$capturedMessages,
                    ) {
                        $capturedMessages[] = $message;
                    });

                $middleware = new PsrLogMiddleware(
                    logger: $logger,
                    operationName: "MyOperation",
                );
                $input = new stdClass();
                $middleware->process($input, fn($data) => $data);

                expect($capturedMessages[1])->toBe(
                    "MyOperation completed with object of type stdClass",
                );
            },
        );

        test("logs null type for null input", function () {
            $capturedMessages = [];
            $logger = Mockery::mock(LoggerInterface::class);
            $logger
                ->shouldReceive("log")
                ->andReturnUsing(function ($level, $message) use (
                    &$capturedMessages,
                ) {
                    $capturedMessages[] = $message;
                });

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "NullTest",
            );
            $middleware->process(null, fn($data) => $data);

            expect($capturedMessages[0])->toBe(
                "NullTest started with object of type null",
            );
        });

        test("logs null type for null output", function () {
            $capturedMessages = [];
            $logger = Mockery::mock(LoggerInterface::class);
            $logger
                ->shouldReceive("log")
                ->andReturnUsing(function ($level, $message) use (
                    &$capturedMessages,
                ) {
                    $capturedMessages[] = $message;
                });

            $middleware = new PsrLogMiddleware(
                logger: $logger,
                operationName: "NullTest",
            );
            $middleware->process(new stdClass(), fn($data) => null);

            expect($capturedMessages[1])->toBe(
                "NullTest completed with object of type null",
            );
        });
    });
});
