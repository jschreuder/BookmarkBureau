<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\ServiceContainer;

use jschreuder\BookmarkBureau\Exception\RepositoryStorageException;
use jschreuder\BookmarkBureau\OperationPipeline\Pipeline;
use jschreuder\BookmarkBureau\OperationMiddleware\PdoTransactionMiddleware;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;
use PDO;

trait DatabaseTrait
{
    // Abstract for method from ConfigTrait
    abstract protected function config(string $key): mixed;

    public function getDb(): PDO
    {
        $dsn = $this->config("db.dsn");
        $dbname = $this->config("db.dbname");
        $user = $this->config("db.user");
        $pass = $this->config("db.pass");

        return $this->createPdoConnection($dsn, $dbname, $user, $pass);
    }

    public function getRateLimitDb(): PDO
    {
        $dsn = $this->config("ratelimit.db.dsn");
        $dbname = $this->config("ratelimit.db.dbname");
        $user = $this->config("ratelimit.db.user");
        $pass = $this->config("ratelimit.db.pass");

        return $this->createPdoConnection($dsn, $dbname, $user, $pass);
    }

    public function getDefaultDbPipeline(): PipelineInterface
    {
        return new Pipeline(new PdoTransactionMiddleware($this->getDb()));
    }

    private function createPdoConnection(
        string $dsn,
        ?string $dbname,
        ?string $user,
        ?string $pass,
    ): PDO {
        // Extract database type from DSN
        $parts = explode(":", $dsn, 2);
        $dbType = strtolower($parts[0]);

        return match ($dbType) {
            "sqlite" => $this->createSqliteDb($dsn),
            "mysql" => $this->createMysqlDb(
                $dsn,
                $dbname ?? "",
                $user ?? "",
                $pass ?? "",
            ),
            default => throw new RepositoryStorageException(
                "Unsupported database type: {$dbType}",
            ),
        };
    }

    private function createSqliteDb(string $dsn): PDO
    {
        // SQLite doesn't support authentication, so only pass DSN and options
        return new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    private function createMysqlDb(
        string $baseDsn,
        string $dbname,
        string $user,
        string $pass,
    ): PDO {
        $dsn = $baseDsn . ";dbname={$dbname};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ];

        return new PDO($dsn, $user, $pass, $options);
    }
}
