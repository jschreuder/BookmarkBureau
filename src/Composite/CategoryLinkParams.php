<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Composite;

use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\Link;

/**
 * Parameter bundle for category-link association operations
 */
final readonly class CategoryLinkParams
{
    public function __construct(
        public Category $category,
        public Link $link,
        public int $sortOrder = -1,
    ) {}
}
