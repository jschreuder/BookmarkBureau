<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Composite;

use Countable;
use IteratorAggregate;
use jschreuder\BookmarkBureau\Composite\CollectionTrait;
use jschreuder\BookmarkBureau\Entity\Dashboard;

/**
 * @implements IteratorAggregate<int, Dashboard>
 */
final readonly class DashboardCollection implements IteratorAggregate, Countable
{
    /** @use CollectionTrait<Dashboard> */
    use CollectionTrait;

    public function __construct(Dashboard ...$dashboards)
    {
        $this->collection = array_values($dashboards);
    }
}
