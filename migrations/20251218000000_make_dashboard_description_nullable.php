<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MakeDashboardDescriptionNullable extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table("dashboards");
        $table->changeColumn("description", "text", ["null" => true])
            ->save();
    }

    public function down(): void
    {
        $table = $this->table("dashboards");
        $table->changeColumn("description", "text", ["null" => false])
            ->save();
    }
}
