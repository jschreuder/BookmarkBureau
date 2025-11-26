<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OperationMiddleware;

use jschreuder\BookmarkBureau\OperationPipeline\PipelineMiddlewareInterface;
use PDO;
use Throwable;

final class PdoTransactionMiddleware implements PipelineMiddlewareInterface
{
    private int $level = 0;

    public function __construct(private readonly PDO $db) {}

    /** @param callable(object|null): (object|null) $next */
    #[\Override]
    public function process(?object $data, callable $next): ?object
    {
        $isOutermost = $this->level === 0;
        if ($isOutermost) {
            $this->db->beginTransaction();
        }
        $this->level++;

        try {
            $result = $next($data);
            $this->level--;
            if ($isOutermost) {
                $this->db->commit();
            }
            return $result;
        } catch (Throwable $e) {
            $this->level = 0;
            if ($isOutermost) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
