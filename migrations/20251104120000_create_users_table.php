<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsersTable extends AbstractMigration
{
    public function change(): void
    {
        // Users table: stores user account information
        $users = $this->table("users", [
            "id" => false,
            "primary_key" => "user_id",
            "engine" => "InnoDB",
            "collation" => "utf8mb4_unicode_ci",
        ]);
        $users
            ->addColumn("user_id", "char", ["limit" => 16])
            ->addColumn("email", "string", ["limit" => 255])
            ->addColumn("password_hash", "string", ["limit" => 255])
            ->addColumn("totp_secret", "string", [
                "limit" => 255,
                "null" => true,
            ])
            ->addColumn("created_at", "timestamp", [
                "default" => "CURRENT_TIMESTAMP",
            ])
            ->addColumn("updated_at", "timestamp", [
                "default" => "CURRENT_TIMESTAMP",
                "update" => "CURRENT_TIMESTAMP",
            ])
            ->addIndex("email", ["name" => "idx_users_email", "unique" => true])
            ->addIndex("created_at", ["name" => "idx_users_created_at"])
            ->create();
    }
}
