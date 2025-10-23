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
    }

    private function doCommit(): void
    {
    }

    private function doRollback(): void
    {
    }
}
