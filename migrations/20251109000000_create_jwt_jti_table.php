<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateJwtJtiTable extends AbstractMigration
{
    public function change(): void
    {
        // JWT JTI whitelist: stores valid JWT token IDs for CLI tokens
        $jtiTable = $this->table("jwt_jti", [
            "id" => false,
            "primary_key" => "jti",
            "engine" => "InnoDB",
            "collation" => "utf8mb4_unicode_ci",
        ]);
        $jtiTable
            ->addColumn("jti", "char", ["limit" => 16])
            ->addColumn("user_id", "char", ["limit" => 16])
            ->addColumn("created_at", "timestamp", [
                "default" => "CURRENT_TIMESTAMP",
            ])
            ->addIndex("user_id", ["name" => "idx_jwt_jti_user_id"])
            ->create();
    }
}
