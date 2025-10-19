<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitialDatabaseSetup extends AbstractMigration
{
    public function change(): void
    {
        // Links table: stores all bookmarked links
        $links = $this->table('links', [
            'id' => false,
            'primary_key' => 'link_id',
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci'
        ]);
        $links->addColumn('link_id', 'char', ['limit' => 16])
              ->addColumn('url', 'text')
              ->addColumn('title', 'string', ['limit' => 255])
              ->addColumn('description', 'text')
              ->addColumn('icon', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', [
                  'default' => 'CURRENT_TIMESTAMP',
                  'update' => 'CURRENT_TIMESTAMP'
              ])
              ->addIndex('created_at', ['name' => 'idx_links_created_at'])
              ->addIndex('title', ['name' => 'idx_links_title'])
              ->addIndex(['title', 'description'], [
                  'name' => 'idx_links_search',
                  'type' => 'fulltext'
              ])
              ->create();

        // Tags table: stores all available tags (name is primary key)
        $tags = $this->table('tags', [
            'id' => false,
            'primary_key' => 'tag_name',
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci'
        ]);
        $tags->addColumn('tag_name', 'string', ['limit' => 100])
             ->addColumn('color', 'string', ['limit' => 6, 'null' => true])
             ->create();

        // Link-Tags junction table: many-to-many relationship
        $linkTags = $this->table('link_tags', [
            'id' => false,
            'primary_key' => ['link_id', 'tag_name'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci'
        ]);
        $linkTags->addColumn('link_id', 'char', ['limit' => 16])
                 ->addColumn('tag_name', 'string', ['limit' => 100])
                 ->addIndex('tag_name', ['name' => 'idx_link_tags_tag'])
                 ->addForeignKey('link_id', 'links', 'link_id', [
                     'delete' => 'CASCADE',
                     'update' => 'RESTRICT',
                     'constraint' => 'fk_link_tags_link'
                 ])
                 ->addForeignKey('tag_name', 'tags', 'tag_name', [
                     'delete' => 'CASCADE',
                     'update' => 'RESTRICT',
                     'constraint' => 'fk_link_tags_tag'
                 ])
                 ->create();

        // Dashboards table: stores dashboard configurations
        $dashboards = $this->table('dashboards', [
            'id' => false,
            'primary_key' => 'dashboard_id',
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci'
        ]);
        $dashboards->addColumn('dashboard_id', 'char', ['limit' => 16])
                   ->addColumn('title', 'string', ['limit' => 255])
                   ->addColumn('description', 'text')
                   ->addColumn('icon', 'string', ['limit' => 100, 'null' => true])
                   ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                   ->addColumn('updated_at', 'timestamp', [
                       'default' => 'CURRENT_TIMESTAMP',
                       'update' => 'CURRENT_TIMESTAMP'
                   ])
                   ->addIndex('title', ['name' => 'idx_dashboards_title'])
                   ->create();

        // Categories table: organizes links within dashboards
        $categories = $this->table('categories', [
            'id' => false,
            'primary_key' => 'category_id',
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci'
        ]);
        $categories->addColumn('category_id', 'char', ['limit' => 16])
                   ->addColumn('dashboard_id', 'char', ['limit' => 16])
                   ->addColumn('title', 'string', ['limit' => 255])
                   ->addColumn('color', 'string', ['limit' => 6, 'null' => true])
                   ->addColumn('sort_order', 'integer', ['default' => 0])
                   ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                   ->addColumn('updated_at', 'timestamp', [
                       'default' => 'CURRENT_TIMESTAMP',
                       'update' => 'CURRENT_TIMESTAMP'
                   ])
                   ->addIndex('dashboard_id', ['name' => 'idx_categories_dashboard'])
                   ->addIndex(['dashboard_id', 'sort_order'], ['name' => 'idx_categories_sort'])
                   ->addForeignKey('dashboard_id', 'dashboards', 'dashboard_id', [
                       'delete' => 'CASCADE',
                       'update' => 'RESTRICT',
                       'constraint' => 'fk_categories_dashboard'
                   ])
                   ->create();

        // Favorites table: stores favorite links for quick access at top of dashboards
        $favorites = $this->table('favorites', [
            'id' => false,
            'primary_key' => ['dashboard_id', 'link_id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci'
        ]);
        $favorites->addColumn('dashboard_id', 'char', ['limit' => 16])
                  ->addColumn('link_id', 'char', ['limit' => 16])
                  ->addColumn('sort_order', 'integer', ['default' => 0])
                  ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                  ->addIndex('link_id', ['name' => 'idx_favorites_link'])
                  ->addIndex(['dashboard_id', 'sort_order'], ['name' => 'idx_favorites_sort'])
                  ->addForeignKey('dashboard_id', 'dashboards', 'dashboard_id', [
                      'delete' => 'CASCADE',
                      'update' => 'RESTRICT',
                      'constraint' => 'fk_favorites_dashboard'
                  ])
                  ->addForeignKey('link_id', 'links', 'link_id', [
                      'delete' => 'CASCADE',
                      'update' => 'RESTRICT',
                      'constraint' => 'fk_favorites_link'
                  ])
                  ->create();

        // Category-Links junction table: links organized in categories on dashboards
        $categoryLinks = $this->table('category_links', [
            'id' => false,
            'primary_key' => ['category_id', 'link_id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci'
        ]);
        $categoryLinks->addColumn('category_id', 'char', ['limit' => 16])
                      ->addColumn('link_id', 'char', ['limit' => 16])
                      ->addColumn('sort_order', 'integer', ['default' => 0])
                      ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                      ->addIndex('link_id', ['name' => 'idx_category_links_link'])
                      ->addIndex(['category_id', 'sort_order'], ['name' => 'idx_category_links_sort'])
                      ->addForeignKey('link_id', 'links', 'link_id', [
                          'delete' => 'CASCADE',
                          'update' => 'RESTRICT',
                          'constraint' => 'fk_category_links_link'
                      ])
                      ->addForeignKey('category_id', 'categories', 'category_id', [
                          'delete' => 'CASCADE',
                          'update' => 'RESTRICT',
                          'constraint' => 'fk_category_links_category'
                      ])
                      ->create();
    }
}
