<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service\UnitOfWork;

use Closure;

/**
 * Units of work control transactions and allow them to be managed. These are
 * part of the service layer and should only be controlled from the service
 * layer as no other layer has the necessary control or knowledge of any
 * transaction.
 */
interface UnitOfWorkInterface
{
    /**
     * Start a transaction (or join existing one if already started)
     */
    public function begin(): void;

    /**
     * Commit the current transaction (only if this is the outermost call)
     */
    public function commit(): void;

    /**
     * Roll back the current transaction
     */
    public function rollback(): void;

    /**
     * Execute a callable within a transaction
     * Automatically handles begin/commit/rollback
     */
    public function transactional(Closure $operation): mixed;

    /**
     * Check if currently in a transaction
     */
    public function isActive(): bool;
}
