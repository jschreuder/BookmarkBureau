<?php

use jschreuder\BookmarkBureau\OperationPipeline\NoPipeline;

describe("NoPipeline", function () {
    describe("run", function () {
        test("should execute operation without any processing", function () {
            $data = new class {
                public int $value = 5;
            };

            $pipeline = new NoPipeline();
            $operation = fn($d) => (object)["value" => $d->value + 1];
            $result = $pipeline->run($operation, $data);

            expect($result->value)->toBe(6);
        });

        test("should return operation result unchanged", function () {
            $data = new class {
                public string $name = "test";
            };

            $pipeline = new NoPipeline();
            $operation = fn($d) => (object)["name" => strtoupper($d->name)];
            $result = $pipeline->run($operation, $data);

            expect($result->name)->toBe("TEST");
        });
    });
});
