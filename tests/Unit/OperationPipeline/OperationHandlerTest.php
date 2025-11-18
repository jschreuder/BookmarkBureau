<?php

use jschreuder\BookmarkBureau\Exception\OperationPipelineException;
use jschreuder\BookmarkBureau\OperationPipeline\OperationHandler;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineMiddlewareInterface;

describe("OperationHandler", function () {
    describe("handle", function () {
        test("should execute operation with no middlewares", function () {
            $data = new class {
                public int $value = 5;
            };

            $handler = new OperationHandler([]);
            $operation = fn($d) => (object) ["value" => $d->value + 1];
            $result = $handler->handle($operation, $data);

            expect($result->value)->toBe(6);
        });

        test("should process through single middleware", function () {
            $data = new class {
                public int $value = 0;
            };

            $middleware = new class implements PipelineMiddlewareInterface {
                public function process(object $data, callable $next): object
                {
                    $data->value += 10;
                    return $next($data);
                }
            };

            $handler = new OperationHandler([$middleware]);
            $operation = fn($d) => (object) ["value" => $d->value + 1];
            $result = $handler->handle($operation, $data);

            expect($result->value)->toBe(11);
        });

        test(
            "should process through multiple middlewares in sequence",
            function () {
                $data = new class {
                    public int $value = 0;
                };

                $middleware1 = new class implements PipelineMiddlewareInterface
                {
                    public function process(
                        object $data,
                        callable $next,
                    ): object {
                        $data->value += 10;
                        return $next($data);
                    }
                };

                $middleware2 = new class implements PipelineMiddlewareInterface
                {
                    public function process(
                        object $data,
                        callable $next,
                    ): object {
                        $data->value *= 2;
                        return $next($data);
                    }
                };

                $handler = new OperationHandler([$middleware1, $middleware2]);
                $operation = fn($d) => (object) ["value" => $d->value + 5];
                $result = $handler->handle($operation, $data);

                // middleware1: 0 + 10 = 10
                // middleware2: 10 * 2 = 20
                // operation: 20 + 5 = 25
                expect($result->value)->toBe(25);
            },
        );

        test(
            "should throw exception when middleware calls next twice",
            function () {
                $data = new class {
                    public int $value = 0;
                };

                $middleware = new class implements PipelineMiddlewareInterface {
                    public function process(
                        object $data,
                        callable $next,
                    ): object {
                        $next($data);
                        // Call next again - should throw
                        return $next($data);
                    }
                };

                $handler = new OperationHandler([$middleware]);
                $operation = fn($d) => $d;

                expect(fn() => $handler->handle($operation, $data))->toThrow(
                    OperationPipelineException::class,
                );
            },
        );

        test("should pass data through middleware chain intact", function () {
            $data = new class {
                public string $value = "original";
            };

            $middleware1 = new class implements PipelineMiddlewareInterface {
                public function process(object $data, callable $next): object
                {
                    $data->value .= "-m1";
                    return $next($data);
                }
            };

            $middleware2 = new class implements PipelineMiddlewareInterface {
                public function process(object $data, callable $next): object
                {
                    $data->value .= "-m2";
                    return $next($data);
                }
            };

            $handler = new OperationHandler([$middleware1, $middleware2]);
            $operation = fn($d) => (object) ["value" => $d->value . "-op"];
            $result = $handler->handle($operation, $data);

            expect($result->value)->toBe("original-m1-m2-op");
        });

        test(
            "should allow middleware to modify returned data from next step",
            function () {
                $data = new class {
                    public int $value = 5;
                };

                $middleware1 = new class implements PipelineMiddlewareInterface
                {
                    public function process(
                        object $data,
                        callable $next,
                    ): object {
                        $result = $next($data);
                        $result->value *= 2;
                        return $result;
                    }
                };

                $middleware2 = new class implements PipelineMiddlewareInterface
                {
                    public function process(
                        object $data,
                        callable $next,
                    ): object {
                        $result = $next($data);
                        $result->value += 10;
                        return $result;
                    }
                };

                $handler = new OperationHandler([$middleware1, $middleware2]);
                $operation = fn($d) => (object) ["value" => $d->value + 3];
                $result = $handler->handle($operation, $data);

                // operation: 5 + 3 = 8
                // middleware2 modifies return: 8 + 10 = 18
                // middleware1 modifies return: 18 * 2 = 36
                expect($result->value)->toBe(36);
            },
        );

        test(
            "should allow middleware to modify both input and output",
            function () {
                $data = new class {
                    public int $value = 1;
                };

                $middleware1 = new class implements PipelineMiddlewareInterface
                {
                    public function process(
                        object $data,
                        callable $next,
                    ): object {
                        $data->value *= 2;
                        $result = $next($data);
                        $result->value += 100;
                        return $result;
                    }
                };

                $middleware2 = new class implements PipelineMiddlewareInterface
                {
                    public function process(
                        object $data,
                        callable $next,
                    ): object {
                        $data->value *= 3;
                        $result = $next($data);
                        $result->value *= 2;
                        return $result;
                    }
                };

                $handler = new OperationHandler([$middleware1, $middleware2]);
                $operation = fn($d) => (object) ["value" => $d->value + 10];
                $result = $handler->handle($operation, $data);

                // middleware1 input: 1 * 2 = 2
                // middleware2 input: 2 * 3 = 6
                // operation: 6 + 10 = 16
                // middleware2 output: 16 * 2 = 32
                // middleware1 output: 32 + 100 = 132
                expect($result->value)->toBe(132);
            },
        );
    });
});
