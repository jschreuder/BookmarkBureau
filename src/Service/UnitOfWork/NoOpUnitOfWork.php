<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service\UnitOfWork;

use Closure;

/**
 * No-op implementation for storage backends without transaction support
 */
final class NoOpUnitOfWork implements UnitOfWorkInterface
{
    use UnitOfWorkTrait;

    private function doBegin(): void
    {
        // NoOp does nothing for backends that don't support Units of Work
    }

    private function doCommit(): void
    {
        // NoOp does nothing for backends that don't support Units of Work
    }

    private function doRollback(): void
    {
        // NoOp does nothing for backends that don't support Units of Work
    }
}
