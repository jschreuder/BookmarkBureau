<?php

use jschreuder\BookmarkBureau\Service\UnitOfWork\NoOpUnitOfWork;
use jschreuder\BookmarkBureau\Service\UnitOfWork\UnitOfWorkInterface;

describe("NoOpUnitOfWork", function () {
    describe("initialization", function () {
        test("creates instance", function () {
            $unitOfWork = new NoOpUnitOfWork();

            expect($unitOfWork)->toBeInstanceOf(NoOpUnitOfWork::class);
        });
    });

    describe("begin transaction", function () {
        test("begin does not throw exception", function () {
            $unitOfWork = new NoOpUnitOfWork();

            $unitOfWork->begin();

            expect(true)->toBeTrue();
        });

        test("is active after begin", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $unitOfWork->begin();

            expect($unitOfWork->isActive())->toBeTrue();
        });

        test("multiple begin calls increment transaction level", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->begin();

            expect($unitOfWork->isActive())->toBeTrue();
        });

        test("is not active before begin", function () {
            $unitOfWork = new NoOpUnitOfWork();

            expect($unitOfWork->isActive())->toBeFalse();
        });
    });

    describe("commit transaction", function () {
        test("commit does not throw exception", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $unitOfWork->begin();

            $unitOfWork->commit();

            expect(true)->toBeTrue();
        });

        test("is not active after commit", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $unitOfWork->begin();
            $unitOfWork->commit();

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test(
            "throws exception when committing without active transaction",
            function () {
                $unitOfWork = new NoOpUnitOfWork();

                expect(fn() => $unitOfWork->commit())->toThrow(
                    RuntimeException::class,
                );
            },
        );

        test("only commits outermost transaction level", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->commit();

            expect($unitOfWork->isActive())->toBeTrue();

            $unitOfWork->commit();
            $unitOfWork->commit();

            expect($unitOfWork->isActive())->toBeFalse();
        });
    });

    describe("rollback transaction", function () {
        test("rollback does not throw exception", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $unitOfWork->begin();

            $unitOfWork->rollback();

            expect(true)->toBeTrue();
        });

        test("is not active after rollback", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $unitOfWork->begin();
            $unitOfWork->rollback();

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test("rolls back all nested levels", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->rollback();

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test(
            "throws exception when rolling back without active transaction",
            function () {
                $unitOfWork = new NoOpUnitOfWork();

                expect(fn() => $unitOfWork->rollback())->toThrow(
                    RuntimeException::class,
                );
            },
        );
    });

    describe("transactional operation", function () {
        test("executes closure", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $executed = false;

            $unitOfWork->transactional(function () use (&$executed) {
                $executed = true;
            });

            expect($executed)->toBeTrue();
        });

        test("returns closure result", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $result = $unitOfWork->transactional(fn() => "success");

            expect($result)->toBe("success");
        });

        test("returns array result from closure", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $data = ["id" => 1, "name" => "test"];
            $result = $unitOfWork->transactional(fn() => $data);

            expect($result)->toBe($data);
        });

        test("is not active after transactional completes", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $unitOfWork->transactional(fn() => null);

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test("handles exception by rolling back", function () {
            $unitOfWork = new NoOpUnitOfWork();

            expect(
                fn() => $unitOfWork->transactional(function () {
                    throw new Exception("Operation failed");
                }),
            )->toThrow(Exception::class, "Operation failed");

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test("re-throws original exception without modification", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $originalException = new Exception("Original error message");

            try {
                $unitOfWork->transactional(function () use (
                    $originalException,
                ) {
                    throw $originalException;
                });
            } catch (Exception $e) {
                expect($e)->toBe($originalException);
                expect($e->getMessage())->toBe("Original error message");
            }
        });

        test("executes multiple operations in sequence", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $results = [];

            $unitOfWork->transactional(function () use (&$results) {
                $results[] = "op1";
                $results[] = "op2";
                $results[] = "op3";
            });

            expect($results)->toBe(["op1", "op2", "op3"]);
        });

        test("can execute nested transactional calls", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $result = null;

            $unitOfWork->transactional(function () use ($unitOfWork, &$result) {
                $result = $unitOfWork->transactional(fn() => "nested");
            });

            expect($result)->toBe("nested");
            expect($unitOfWork->isActive())->toBeFalse();
        });
    });

    describe("transaction state management", function () {
        test("is not active initially", function () {
            $unitOfWork = new NoOpUnitOfWork();

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test("is active during transaction", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $unitOfWork->begin();

            expect($unitOfWork->isActive())->toBeTrue();
        });

        test("is inactive after commit", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $unitOfWork->begin();
            $unitOfWork->commit();

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test("is inactive after rollback", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $unitOfWork->begin();
            $unitOfWork->rollback();

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test("supports multiple sequential transactions", function () {
            $unitOfWork = new NoOpUnitOfWork();

            // First transaction
            $unitOfWork->begin();
            expect($unitOfWork->isActive())->toBeTrue();
            $unitOfWork->commit();
            expect($unitOfWork->isActive())->toBeFalse();

            // Second transaction
            $unitOfWork->begin();
            expect($unitOfWork->isActive())->toBeTrue();
            $unitOfWork->commit();
            expect($unitOfWork->isActive())->toBeFalse();

            // Third transaction using transactional
            $unitOfWork->transactional(fn() => null);
            expect($unitOfWork->isActive())->toBeFalse();
        });
    });

    describe("nested transaction handling", function () {
        test("handles multiple nested begin calls", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->begin();

            expect($unitOfWork->isActive())->toBeTrue();
        });

        test(
            "requires matching number of commits for nested transactions",
            function () {
                $unitOfWork = new NoOpUnitOfWork();
                $unitOfWork->begin();
                $unitOfWork->begin();
                $unitOfWork->begin();
                $unitOfWork->commit();

                expect($unitOfWork->isActive())->toBeTrue();

                $unitOfWork->commit();
                expect($unitOfWork->isActive())->toBeTrue();

                $unitOfWork->commit();
                expect($unitOfWork->isActive())->toBeFalse();
            },
        );

        test("rollback exits all nested levels at once", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->rollback();

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test("mixed begin and commit levels", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $unitOfWork->begin(); // Level 1
            $unitOfWork->begin(); // Level 2
            $unitOfWork->begin(); // Level 3
            $unitOfWork->commit(); // Level 2
            $unitOfWork->begin(); // Level 3
            $unitOfWork->commit(); // Level 2
            $unitOfWork->commit(); // Level 1
            $unitOfWork->commit(); // Level 0

            expect($unitOfWork->isActive())->toBeFalse();
        });
    });

    describe("no-op behavior", function () {
        test("does not perform any actual transaction operations", function () {
            $unitOfWork = new NoOpUnitOfWork();

            // These should all complete without any side effects
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->commit();
            $unitOfWork->begin();
            $unitOfWork->rollback();

            expect(true)->toBeTrue();
        });

        test(
            "suitable for storage backends without transaction support",
            function () {
                $unitOfWork = new NoOpUnitOfWork();

                // Simulating operations on non-transactional storage
                $result = $unitOfWork->transactional(function () {
                    return ["data" => "stored"];
                });

                expect($result)->toBe(["data" => "stored"]);
            },
        );

        test(
            "preserves closure execution even without actual transactions",
            function () {
                $unitOfWork = new NoOpUnitOfWork();
                $sideEffect = 0;

                $unitOfWork->transactional(function () use (&$sideEffect) {
                    $sideEffect += 5;
                    $sideEffect *= 2;
                    $sideEffect -= 3;
                });

                expect($sideEffect)->toBe(7);
            },
        );
    });

    describe("interface compliance", function () {
        test("implements UnitOfWorkInterface", function () {
            $unitOfWork = new NoOpUnitOfWork();

            expect($unitOfWork)->toBeInstanceOf(UnitOfWorkInterface::class);
        });

        test("has all required interface methods", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $reflection = new ReflectionClass($unitOfWork);

            expect($reflection->hasMethod("begin"))->toBeTrue();
            expect($reflection->hasMethod("commit"))->toBeTrue();
            expect($reflection->hasMethod("rollback"))->toBeTrue();
            expect($reflection->hasMethod("transactional"))->toBeTrue();
            expect($reflection->hasMethod("isActive"))->toBeTrue();
        });
    });

    describe("edge cases", function () {
        test("handles transactional with no operations", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $result = $unitOfWork->transactional(function () {
                // Empty closure
            });

            expect($result)->toBeNull();
        });

        test("handles transactional returning null", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $result = $unitOfWork->transactional(fn() => null);

            expect($result)->toBeNull();
        });

        test("handles transactional returning false", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $result = $unitOfWork->transactional(fn() => false);

            expect($result)->toBeFalse();
        });

        test("handles transactional returning zero", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $result = $unitOfWork->transactional(fn() => 0);

            expect($result)->toBe(0);
        });

        test("handles transactional returning empty array", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $result = $unitOfWork->transactional(fn() => []);

            expect($result)->toBe([]);
        });

        test("handles transactional returning empty string", function () {
            $unitOfWork = new NoOpUnitOfWork();
            $result = $unitOfWork->transactional(fn() => "");

            expect($result)->toBe("");
        });
    });
});
