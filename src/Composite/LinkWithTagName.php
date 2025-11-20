<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Composite;

use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Entity\Value\TagName;

/**
 * View object representing a category with its links
 */
final readonly class LinkWithTagName
{
    public function __construct(public Link $link, public TagName $tagName) {}
}
