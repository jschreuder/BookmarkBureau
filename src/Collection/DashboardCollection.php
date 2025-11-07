<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Collection;

use Countable;
use IteratorAggregate;
use jschreuder\BookmarkBureau\Collection\CollectionTrait;
use jschreuder\BookmarkBureau\Entity\Dashboard;

final readonly class DashboardCollection implements IteratorAggregate, Countable
{
    use CollectionTrait;

    public function __construct(Dashboard ...$dashboards)
    {
        $this->collection = $dashboards;
    }
}
