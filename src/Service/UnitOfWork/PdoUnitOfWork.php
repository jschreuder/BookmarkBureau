<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service\UnitOfWork;

use PDO;

final class PdoUnitOfWork implements UnitOfWorkInterface
{
    use UnitOfWorkTrait;

    public function __construct(private readonly PDO $pdo) {}

    private function doBegin(): void
    {
        $this->pdo->beginTransaction();
    }

    private function doCommit(): void
    {
        $this->pdo->commit();
    }

    private function doRollback(): void
    {
        $this->pdo->rollBack();
    }
}
