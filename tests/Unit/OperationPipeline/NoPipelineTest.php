<?php

use jschreuder\BookmarkBureau\OperationPipeline\NoPipeline;

describe("NoPipeline", function () {
    describe("run", function () {
        test("should execute operation without any processing", function () {
            $data = new class {
                public int $value = 5;
            };

            $pipeline = new NoPipeline();
            $operation = fn($d) => (object) ["value" => $d->value + 1];
            $result = $pipeline->run($operation, $data);

            expect($result->value)->toBe(6);
        });

        test("should return operation result unchanged", function () {
            $data = new class {
                public string $name = "test";
            };

            $pipeline = new NoPipeline();
            $operation = fn($d) => (object) ["name" => strtoupper($d->name)];
            $result = $pipeline->run($operation, $data);

            expect($result->name)->toBe("TEST");
        });

        test("should handle null data", function () {
            $pipeline = new NoPipeline();
            $operation = fn($d) => $d === null
                ? (object) ["created" => true]
                : $d;
            $result = $pipeline->run($operation, null);

            expect($result->created)->toBeTrue();
        });

        test("should handle null returned from operation", function () {
            $data = new class {
                public int $value = 5;
            };

            $pipeline = new NoPipeline();
            $operation = fn($d) => null;
            $result = $pipeline->run($operation, $data);

            expect($result)->toBeNull();
        });

        test("should handle no data parameter (defaults to null)", function () {
            $pipeline = new NoPipeline();
            $operation = fn($d) => (object) ["received_null" => $d === null];
            $result = $pipeline->run($operation);

            expect($result->received_null)->toBeTrue();
        });

        test("should pass null data directly to operation", function () {
            $receivedData = "not set";
            $pipeline = new NoPipeline();
            $operation = function ($d) use (&$receivedData) {
                $receivedData = $d;
                return $d;
            };
            $pipeline->run($operation, null);

            expect($receivedData)->toBeNull();
        });
    });
});
