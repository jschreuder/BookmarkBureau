<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Composite;

use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Link;

/**
 * Parameter bundle for favorite association operations
 */
final readonly class FavoriteParams
{
    public function __construct(
        public Dashboard $dashboard,
        public Link $link,
        public ?int $sortOrder = null,
    ) {}
}
