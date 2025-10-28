<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service\UnitOfWork;

use Closure;
use jschreuder\BookmarkBureau\Exception\InactiveUnitOfWorkException;

trait UnitOfWorkTrait
{
    private int $transactionLevel = 0;

    abstract private function doBegin(): void;
    abstract private function doCommit(): void;
    abstract private function doRollback(): void;

    public function begin(): void
    {
        if ($this->transactionLevel === 0) {
            $this->doBegin();
        }
        $this->transactionLevel++;
    }

    public function commit(): void
    {
        if ($this->transactionLevel === 0) {
            throw new InactiveUnitOfWorkException('No active transaction to commit');
        }

        $this->transactionLevel--;

        if ($this->transactionLevel === 0) {
            $this->doCommit();
        }
    }

    public function rollback(): void
    {
        if ($this->transactionLevel === 0) {
            throw new InactiveUnitOfWorkException('No active transaction to rollback');
        }

        $this->transactionLevel = 0;
        $this->doRollback();
    }

    public function transactional(Closure $operation): mixed
    {
        $this->begin();

        try {
            $result = $operation();
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function isActive(): bool
    {
        return $this->transactionLevel > 0;
    }
}
