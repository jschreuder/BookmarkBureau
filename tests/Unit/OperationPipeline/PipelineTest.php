<?php

use jschreuder\BookmarkBureau\OperationPipeline\Pipeline;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineMiddlewareInterface;

describe("Pipeline", function () {
    describe("run", function () {
        test("should execute operation with no middlewares", function () {
            $data = new class {
                public int $value = 0;
            };

            $pipeline = new Pipeline();
            $operation = fn($d) => (object) ["value" => $d->value + 1];
            $result = $pipeline->run($operation, $data);

            expect($result->value)->toBe(1);
        });

        test("should process through single middleware", function () {
            $data = new class {
                public int $value = 0;
            };

            $middleware = new class implements PipelineMiddlewareInterface {
                public function process(?object $data, callable $next): ?object
                {
                    $data->value += 10;
                    return $next($data);
                }
            };

            $pipeline = new Pipeline($middleware);
            $operation = fn($d) => (object) ["value" => $d->value + 1];
            $result = $pipeline->run($operation, $data);

            expect($result->value)->toBe(11);
        });

        test(
            "should process through multiple middlewares in order",
            function () {
                $data = new class {
                    public int $value = 0;
                };

                $middleware1 = new class implements PipelineMiddlewareInterface
                {
                    public function process(
                        ?object $data,
                        callable $next,
                    ): ?object {
                        $data->value += 10;
                        return $next($data);
                    }
                };

                $middleware2 = new class implements PipelineMiddlewareInterface
                {
                    public function process(
                        ?object $data,
                        callable $next,
                    ): ?object {
                        $data->value *= 2;
                        return $next($data);
                    }
                };

                $pipeline = new Pipeline($middleware1, $middleware2);
                $operation = fn($d) => (object) ["value" => $d->value + 5];
                $result = $pipeline->run($operation, $data);

                // middleware1: 0 + 10 = 10
                // middleware2: 10 * 2 = 20
                // operation: 20 + 5 = 25
                expect($result->value)->toBe(25);
            },
        );

        test("should allow middleware to modify data", function () {
            $data = new class {
                public string $name = "original";
            };

            $middleware = new class implements PipelineMiddlewareInterface {
                public function process(?object $data, callable $next): ?object
                {
                    $data->name = "modified";
                    return $next($data);
                }
            };

            $pipeline = new Pipeline($middleware);
            $operation = fn($d) => $d;
            $result = $pipeline->run($operation, $data);

            expect($result->name)->toBe("modified");
        });

        test("should handle null data through pipeline", function () {
            $middleware = new class implements PipelineMiddlewareInterface {
                public function process(?object $data, callable $next): ?object
                {
                    return $next($data);
                }
            };

            $pipeline = new Pipeline($middleware);
            $operation = fn($d) => $d === null
                ? (object) ["created" => true]
                : $d;
            $result = $pipeline->run($operation, null);

            expect($result->created)->toBeTrue();
        });

        test(
            "should handle null returned from operation in pipeline",
            function () {
                $data = new class {
                    public int $value = 5;
                };

                $middleware = new class implements PipelineMiddlewareInterface {
                    public function process(
                        ?object $data,
                        callable $next,
                    ): ?object {
                        return $next($data);
                    }
                };

                $pipeline = new Pipeline($middleware);
                $operation = fn($d) => null;
                $result = $pipeline->run($operation, $data);

                expect($result)->toBeNull();
            },
        );

        test(
            "should handle pipeline without data parameter (defaults to null)",
            function () {
                $pipeline = new Pipeline();
                $operation = fn($d) => (object) [
                    "received_null" => $d === null,
                ];
                $result = $pipeline->run($operation);

                expect($result->received_null)->toBeTrue();
            },
        );

        test(
            "should allow middleware in pipeline to create object from null",
            function () {
                $middleware = new class implements PipelineMiddlewareInterface {
                    public function process(
                        ?object $data,
                        callable $next,
                    ): ?object {
                        if ($data === null) {
                            $data = (object) ["initialized" => true];
                        }
                        return $next($data);
                    }
                };

                $pipeline = new Pipeline($middleware);
                $operation = fn($d) => (object) [
                    "value" => $d->initialized ? 42 : 0,
                ];
                $result = $pipeline->run($operation, null);

                expect($result->value)->toBe(42);
            },
        );
    });

    describe("withMiddleware", function () {
        test("should add middleware and return new pipeline", function () {
            $pipeline1 = new Pipeline();
            $middleware = new class implements PipelineMiddlewareInterface {
                public function process(?object $data, callable $next): ?object
                {
                    return $next($data);
                }
            };

            $pipeline2 = $pipeline1->withMiddleware($middleware);

            expect($pipeline2)->not->toBe($pipeline1);
        });

        test("should allow chaining middleware", function () {
            $data = new class {
                public int $value = 0;
            };

            $middleware1 = new class implements PipelineMiddlewareInterface {
                public function process(?object $data, callable $next): ?object
                {
                    $data->value += 5;
                    return $next($data);
                }
            };

            $middleware2 = new class implements PipelineMiddlewareInterface {
                public function process(?object $data, callable $next): ?object
                {
                    $data->value *= 2;
                    return $next($data);
                }
            };

            $pipeline = new Pipeline()
                ->withMiddleware($middleware1)
                ->withMiddleware($middleware2);

            $operation = fn($d) => $d;
            $result = $pipeline->run($operation, $data);

            // middleware1: 0 + 5 = 5
            // middleware2: 5 * 2 = 10
            expect($result->value)->toBe(10);
        });
    });
});
