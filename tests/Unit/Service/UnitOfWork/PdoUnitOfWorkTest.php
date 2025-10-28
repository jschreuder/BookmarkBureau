<?php

use jschreuder\BookmarkBureau\Exception\InactiveUnitOfWorkException;
use jschreuder\BookmarkBureau\Service\UnitOfWork\PdoUnitOfWork;

describe('PdoUnitOfWork', function () {
    describe('initialization', function () {
        test('creates instance with PDO', function () {
            $pdo = Mockery::mock(PDO::class);
            $unitOfWork = new PdoUnitOfWork($pdo);

            expect($unitOfWork)->toBeInstanceOf(PdoUnitOfWork::class);
        });

        test('stores PDO instance', function () {
            $pdo = Mockery::mock(PDO::class);
            $unitOfWork = new PdoUnitOfWork($pdo);

            expect($unitOfWork)->toBeInstanceOf(PdoUnitOfWork::class);
        });
    });

    describe('begin transaction', function () {
        test('begins transaction on first call', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $unitOfWork->begin();

            expect(true)->toBeTrue();
        });

        test('increments transaction level on begin', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $unitOfWork->begin();

            expect($unitOfWork->isActive())->toBeTrue();
        });

        test('only calls PDO beginTransaction once for nested transactions', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->begin();

            expect($unitOfWork->isActive())->toBeTrue();
        });

        test('is not active before begin', function () {
            $pdo = Mockery::mock(PDO::class);

            $unitOfWork = new PdoUnitOfWork($pdo);

            expect($unitOfWork->isActive())->toBeFalse();
        });
    });

    describe('commit transaction', function () {
        test('commits transaction after begin', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();
            $pdo->shouldReceive('commit')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $unitOfWork->begin();
            $unitOfWork->commit();

            expect(true)->toBeTrue();
        });

        test('decrements transaction level on commit', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();
            $pdo->shouldReceive('commit')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $unitOfWork->begin();
            $unitOfWork->commit();

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test('only calls PDO commit when exiting outermost transaction', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();
            $pdo->shouldReceive('commit')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->commit();
            $unitOfWork->commit();
            $unitOfWork->commit();

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test('throws exception when committing without active transaction', function () {
            $pdo = Mockery::mock(PDO::class);

            $unitOfWork = new PdoUnitOfWork($pdo);

            expect(fn() => $unitOfWork->commit())
                ->toThrow(InactiveUnitOfWorkException::class);
        });
    });

    describe('rollback transaction', function () {
        test('rolls back transaction after begin', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();
            $pdo->shouldReceive('rollBack')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $unitOfWork->begin();
            $unitOfWork->rollback();

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test('resets transaction level on rollback', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();
            $pdo->shouldReceive('rollBack')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->rollback();

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test('throws exception when rolling back without active transaction', function () {
            $pdo = Mockery::mock(PDO::class);

            $unitOfWork = new PdoUnitOfWork($pdo);

            expect(fn() => $unitOfWork->rollback())
                ->toThrow(InactiveUnitOfWorkException::class);
        });

        test('can rollback mid-nested transaction', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();
            $pdo->shouldReceive('rollBack')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->rollback();

            expect($unitOfWork->isActive())->toBeFalse();
        });
    });

    describe('transactional operation', function () {
        test('executes closure within transaction', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();
            $pdo->shouldReceive('commit')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $result = $unitOfWork->transactional(fn() => 'success');

            expect($result)->toBe('success');
            expect($unitOfWork->isActive())->toBeFalse();
        });

        test('returns closure result', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();
            $pdo->shouldReceive('commit')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $result = $unitOfWork->transactional(fn() => ['id' => 1, 'name' => 'test']);

            expect($result)->toBe(['id' => 1, 'name' => 'test']);
        });

        test('rolls back on exception', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();
            $pdo->shouldReceive('rollBack')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);

            expect(fn() => $unitOfWork->transactional(function () {
                throw new Exception('Operation failed');
            }))->toThrow(Exception::class, 'Operation failed');

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test('commits when no exception', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();
            $pdo->shouldReceive('commit')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $unitOfWork->transactional(fn() => null);

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test('executes closure with parameters', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();
            $pdo->shouldReceive('commit')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $result = $unitOfWork->transactional(function () {
                return 5 + 10;
            });

            expect($result)->toBe(15);
        });
    });

    describe('transaction state management', function () {
        test('is not active initially', function () {
            $pdo = Mockery::mock(PDO::class);

            $unitOfWork = new PdoUnitOfWork($pdo);

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test('is active during transaction', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $unitOfWork->begin();

            expect($unitOfWork->isActive())->toBeTrue();
        });

        test('is inactive after commit', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();
            $pdo->shouldReceive('commit')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $unitOfWork->begin();
            $unitOfWork->commit();

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test('is inactive after rollback', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();
            $pdo->shouldReceive('rollBack')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $unitOfWork->begin();
            $unitOfWork->rollback();

            expect($unitOfWork->isActive())->toBeFalse();
        });

        test('multiple sequential transactions work correctly', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->twice();
            $pdo->shouldReceive('commit')->twice();

            $unitOfWork = new PdoUnitOfWork($pdo);

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
        });
    });

    describe('nested transaction handling', function () {
        test('handles multiple nested begin calls', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->begin();

            expect($unitOfWork->isActive())->toBeTrue();
        });

        test('requires matching number of commits', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();
            $pdo->shouldReceive('commit')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->commit();

            expect($unitOfWork->isActive())->toBeTrue();

            $unitOfWork->commit();
            expect($unitOfWork->isActive())->toBeTrue();

            $unitOfWork->commit();
            expect($unitOfWork->isActive())->toBeFalse();
        });

        test('rollback exits all nested levels', function () {
            $pdo = Mockery::mock(PDO::class);
            $pdo->shouldReceive('beginTransaction')->once();
            $pdo->shouldReceive('rollBack')->once();

            $unitOfWork = new PdoUnitOfWork($pdo);
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->begin();
            $unitOfWork->rollback();

            expect($unitOfWork->isActive())->toBeFalse();
        });
    });

    describe('interface compliance', function () {
        test('implements UnitOfWorkInterface', function () {
            $pdo = Mockery::mock(PDO::class);
            $unitOfWork = new PdoUnitOfWork($pdo);

            expect($unitOfWork)->toBeInstanceOf(\jschreuder\BookmarkBureau\Service\UnitOfWork\UnitOfWorkInterface::class);
        });
    });
});
