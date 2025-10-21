<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\View;

use jschreuder\BookmarkBureau\Collection\LinkCollection;
use jschreuder\BookmarkBureau\Entity\Dashboard;

/**
 * View object containing all data needed to render a complete dashboard
 */
final readonly class DashboardView
{
    public function __construct(
        public Dashboard $dashboard,
        public CategoryWithLinksCollection $categories,
        public LinkCollection $favorites
    ) {}
}
