<?php

use jschreuder\BookmarkBureau\OperationPipeline\PdoTransactionMiddleware;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineMiddlewareInterface;

describe("PdoTransactionMiddleware", function () {
    describe("initialization", function () {
        test("creates instance with PDO", function () {
            $pdo = Mockery::mock(PDO::class);
            $middleware = new PdoTransactionMiddleware($pdo);

            expect($middleware)->toBeInstanceOf(
                PdoTransactionMiddleware::class,
            );
        });

        test("implements PipelineMiddlewareInterface", function () {
            $pdo = Mockery::mock(PDO::class);
            $middleware = new PdoTransactionMiddleware($pdo);

            expect($middleware)->toBeInstanceOf(
                PipelineMiddlewareInterface::class,
            );
        });
    });

    describe("transaction management", function () {
        test("begins transaction on first process call", function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("beginTransaction")->once()->andReturn(true);
            $pdo->shouldReceive("commit")->once()->andReturn(true);

            $middleware = new PdoTransactionMiddleware($pdo);
            $data = new stdClass();
            $middleware->process($data, fn($d) => $d);

            expect(true)->toBeTrue();
        });

        test("commits transaction after successful operation", function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("beginTransaction")->once()->andReturn(true);
            $pdo->shouldReceive("commit")->once()->andReturn(true);

            $middleware = new PdoTransactionMiddleware($pdo);
            $data = new stdClass();
            $middleware->process($data, fn($d) => $d);

            expect(true)->toBeTrue();
        });

        test("rolls back on exception", function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("beginTransaction")->once()->andReturn(true);
            $pdo->shouldReceive("rollBack")->once()->andReturn(true);

            $middleware = new PdoTransactionMiddleware($pdo);
            $data = new stdClass();

            expect(
                fn() => $middleware->process(
                    $data,
                    fn($d) => throw new Exception("Operation failed"),
                ),
            )->toThrow(Exception::class, "Operation failed");
        });

        test("resets transaction level on exception", function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("beginTransaction")->twice()->andReturn(true);
            $pdo->shouldReceive("rollBack")->once()->andReturn(true);
            $pdo->shouldReceive("commit")->once()->andReturn(true);

            $middleware = new PdoTransactionMiddleware($pdo);
            $data = new stdClass();

            try {
                $middleware->process(
                    $data,
                    fn($d) => throw new Exception("Operation failed"),
                );
            } catch (Exception) {
                // Expected
            }

            // If we call process again, it should start a fresh transaction
            $middleware->process($data, fn($d) => $d);

            expect(true)->toBeTrue();
        });
    });

    describe("nested process calls within same middleware", function () {
        test(
            "only calls PDO beginTransaction once for nested calls on same middleware instance",
            function () {
                $pdo = Mockery::mock(PDO::class);
                $pdo->shouldReceive("beginTransaction")
                    ->once()
                    ->andReturn(true);
                $pdo->shouldReceive("commit")->once()->andReturn(true);

                $middleware = new PdoTransactionMiddleware($pdo);
                $data = new stdClass();

                // Call process twice within the same operation to simulate nesting
                $middleware->process($data, function ($d) use ($middleware) {
                    // This creates a nested middleware chain through same instance
                    return $middleware->process($d, fn($inner) => $inner);
                });

                expect(true)->toBeTrue();
            },
        );

        test("only calls PDO commit when exiting outermost level", function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("beginTransaction")->once()->andReturn(true);
            $pdo->shouldReceive("commit")->once()->andReturn(true);

            $middleware = new PdoTransactionMiddleware($pdo);
            $data = new stdClass();

            $middleware->process($data, function ($d) use ($middleware) {
                return $middleware->process($d, function ($inner) use (
                    $middleware,
                ) {
                    return $middleware->process(
                        $inner,
                        fn($deepest) => $deepest,
                    );
                });
            });

            expect(true)->toBeTrue();
        });

        test("rolls back all nested levels on exception", function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("beginTransaction")->once()->andReturn(true);
            $pdo->shouldReceive("rollBack")->once()->andReturn(true);

            $middleware = new PdoTransactionMiddleware($pdo);
            $data = new stdClass();

            expect(
                fn() => $middleware->process($data, function ($d) use (
                    $middleware,
                ) {
                    return $middleware->process($d, function ($inner) use (
                        $middleware,
                    ) {
                        return $middleware->process(
                            $inner,
                            fn($deepest) => throw new Exception("Deep failure"),
                        );
                    });
                }),
            )->toThrow(Exception::class, "Deep failure");
        });

        test(
            "resets level to zero on exception at any nested level",
            function () {
                $pdo = Mockery::mock(PDO::class);
                $pdo->shouldReceive("beginTransaction")
                    ->twice()
                    ->andReturn(true);
                $pdo->shouldReceive("rollBack")->once()->andReturn(true);
                $pdo->shouldReceive("commit")->once()->andReturn(true);

                $middleware = new PdoTransactionMiddleware($pdo);
                $data = new stdClass();

                // First operation that fails in nested chain
                try {
                    $middleware->process($data, function ($d) use (
                        $middleware,
                    ) {
                        return $middleware->process(
                            $d,
                            fn($inner) => throw new Exception("Failed"),
                        );
                    });
                } catch (Exception) {
                    // Expected
                }

                // Second operation should work normally with fresh transaction
                $middleware->process($data, fn($d) => $d);

                expect(true)->toBeTrue();
            },
        );
    });

    describe("independent middleware instances", function () {
        test(
            "each middleware instance manages its own transaction level",
            function () {
                $pdo = Mockery::mock(PDO::class);
                $pdo->shouldReceive("beginTransaction")
                    ->twice()
                    ->andReturn(true);
                $pdo->shouldReceive("commit")->twice()->andReturn(true);

                $middleware1 = new PdoTransactionMiddleware($pdo);
                $middleware2 = new PdoTransactionMiddleware($pdo);

                $data = new stdClass();

                // Each middleware manages its own transaction
                $middleware1->process($data, fn($d) => $d);
                $middleware2->process($data, fn($d) => $d);

                expect(true)->toBeTrue();
            },
        );

        test(
            "independent instances do not affect each other's transaction level",
            function () {
                $pdo = Mockery::mock(PDO::class);
                $pdo->shouldReceive("beginTransaction")
                    ->twice()
                    ->andReturn(true);
                $pdo->shouldReceive("rollBack")->once()->andReturn(true);
                $pdo->shouldReceive("commit")->once()->andReturn(true);

                $middleware1 = new PdoTransactionMiddleware($pdo);
                $middleware2 = new PdoTransactionMiddleware($pdo);

                $data = new stdClass();

                // middleware1 fails
                try {
                    $middleware1->process(
                        $data,
                        fn($d) => throw new Exception("m1 failed"),
                    );
                } catch (Exception) {
                    // Expected
                }

                // middleware2 should work independently
                $middleware2->process($data, fn($d) => $d);

                expect(true)->toBeTrue();
            },
        );
    });

    describe("data flow", function () {
        test("passes data through to next callable", function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("beginTransaction")->once()->andReturn(true);
            $pdo->shouldReceive("commit")->once()->andReturn(true);

            $middleware = new PdoTransactionMiddleware($pdo);
            $data = new stdClass();
            $data->value = 42;
            $receivedData = null;

            $middleware->process($data, function ($d) use (&$receivedData) {
                $receivedData = $d;
                return $d;
            });

            expect($receivedData)->toBe($data);
            expect($receivedData->value)->toBe(42);
        });

        test("returns result from next callable", function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("beginTransaction")->once()->andReturn(true);
            $pdo->shouldReceive("commit")->once()->andReturn(true);

            $middleware = new PdoTransactionMiddleware($pdo);
            $inputData = new stdClass();
            $outputData = new stdClass();
            $outputData->processed = true;

            $result = $middleware->process($inputData, fn($d) => $outputData);

            expect($result)->toBe($outputData);
            expect($result->processed)->toBeTrue();
        });

        test("handles null data", function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("beginTransaction")->once()->andReturn(true);
            $pdo->shouldReceive("commit")->once()->andReturn(true);

            $middleware = new PdoTransactionMiddleware($pdo);

            $result = $middleware->process(
                null,
                fn($d) => $d === null ? (object) ["created" => true] : $d,
            );

            expect($result->created)->toBeTrue();
        });

        test("handles null returned from next callable", function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("beginTransaction")->once()->andReturn(true);
            $pdo->shouldReceive("commit")->once()->andReturn(true);

            $middleware = new PdoTransactionMiddleware($pdo);
            $data = new stdClass();

            $result = $middleware->process($data, fn($d) => null);

            expect($result)->toBeNull();
        });

        test("maintains data integrity through transaction", function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("beginTransaction")->once()->andReturn(true);
            $pdo->shouldReceive("commit")->once()->andReturn(true);

            $middleware = new PdoTransactionMiddleware($pdo);
            $inputData = new stdClass();
            $inputData->name = "Test";
            $inputData->count = 10;

            $result = $middleware->process($inputData, function ($d) {
                $d->count += 5;
                return $d;
            });

            expect($result->name)->toBe("Test");
            expect($result->count)->toBe(15);
        });
    });

    describe("exception handling", function () {
        test("re-throws original exception", function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("beginTransaction")->once()->andReturn(true);
            $pdo->shouldReceive("rollBack")->once()->andReturn(true);

            $middleware = new PdoTransactionMiddleware($pdo);
            $customException = new RuntimeException("Custom error");

            expect(
                fn() => $middleware->process(
                    new stdClass(),
                    fn($d) => throw $customException,
                ),
            )->toThrow(RuntimeException::class, "Custom error");
        });

        test("calls rollBack even for throwable exceptions", function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("beginTransaction")->once()->andReturn(true);
            $pdo->shouldReceive("rollBack")->once()->andReturn(true);

            $middleware = new PdoTransactionMiddleware($pdo);

            try {
                $middleware->process(
                    new stdClass(),
                    fn($d) => throw new Error("Fatal error"),
                );
            } catch (Error) {
                // Expected
            }

            expect(true)->toBeTrue();
        });

        test("exception from nested middleware triggers rollback", function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("beginTransaction")->once()->andReturn(true);
            $pdo->shouldReceive("rollBack")->once()->andReturn(true);

            $middleware = new PdoTransactionMiddleware($pdo);
            $innerException = new Exception("Inner failure");

            expect(
                fn() => $middleware->process(
                    new stdClass(),
                    fn($d) => throw $innerException,
                ),
            )->toThrow(Exception::class, "Inner failure");
        });
    });

    describe("multiple sequential transactions", function () {
        test("handles multiple sequential transactions correctly", function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive("beginTransaction")->twice()->andReturn(true);
            $pdo->shouldReceive("commit")->twice()->andReturn(true);

            $middleware = new PdoTransactionMiddleware($pdo);
            $data = new stdClass();

            // First transaction
            $result1 = $middleware->process(
                $data,
                fn($d) => (object) ["transaction" => 1],
            );
            expect($result1->transaction)->toBe(1);

            // Second transaction - should work independently
            $result2 = $middleware->process(
                $data,
                fn($d) => (object) ["transaction" => 2],
            );
            expect($result2->transaction)->toBe(2);
        });
    });

    describe("transaction state", function () {
        test("begins transaction at outermost level", function () {
            $pdo = Mockery::mock(PDO::class);
            $callOrder = [];

            $pdo->shouldReceive("beginTransaction")->andReturnUsing(
                function () use (&$callOrder) {
                    $callOrder[] = "begin";
                    return true;
                },
            );
            $pdo->shouldReceive("commit")->andReturnUsing(function () use (
                &$callOrder,
            ) {
                $callOrder[] = "commit";
                return true;
            });

            $middleware = new PdoTransactionMiddleware($pdo);
            $middleware->process(new stdClass(), fn($d) => $d);

            expect($callOrder)->toBe(["begin", "commit"]);
        });

        test("commits transaction at outermost level only", function () {
            $pdo = Mockery::mock(PDO::class);
            $commitCount = 0;

            $pdo->shouldReceive("beginTransaction")->once()->andReturn(true);
            $pdo->shouldReceive("commit")->andReturnUsing(function () use (
                &$commitCount,
            ) {
                $commitCount++;
                return true;
            });

            $middleware = new PdoTransactionMiddleware($pdo);

            $middleware->process(new stdClass(), function ($d) use (
                $middleware,
            ) {
                return $middleware->process($d, fn($inner) => $inner);
            });

            expect($commitCount)->toBe(1);
        });

        test(
            "calls rollBack at outermost level when exception occurs",
            function () {
                $pdo = Mockery::mock(PDO::class);
                $rollbackCount = 0;

                $pdo->shouldReceive("beginTransaction")
                    ->once()
                    ->andReturn(true);
                $pdo->shouldReceive("rollBack")->andReturnUsing(
                    function () use (&$rollbackCount) {
                        $rollbackCount++;
                        return true;
                    },
                );

                $middleware = new PdoTransactionMiddleware($pdo);

                try {
                    $middleware->process(new stdClass(), function ($d) use (
                        $middleware,
                    ) {
                        return $middleware->process(
                            $d,
                            fn($inner) => throw new Exception("Failed"),
                        );
                    });
                } catch (Exception) {
                    // Expected
                }

                expect($rollbackCount)->toBe(1);
            },
        );
    });
});
