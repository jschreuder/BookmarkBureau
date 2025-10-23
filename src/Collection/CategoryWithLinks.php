<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Collection;

use jschreuder\BookmarkBureau\Collection\LinkCollection;
use jschreuder\BookmarkBureau\Entity\Category;

/**
 * View object representing a category with its links
 */
final readonly class CategoryWithLinks
{
    public function __construct(
        public Category $category,
        public LinkCollection $links
    ) {}
}
