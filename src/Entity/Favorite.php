<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity;

use DateTimeInterface;

final class Favorite implements EntityEqualityInterface
{

    public readonly Dashboard $dashboard;

    public readonly Link $link;

    public int $sortOrder {
        set {
            $this->sortOrder = $value;
        }
    }

    public readonly DateTimeInterface $createdAt;

    public function __construct(
        Dashboard $dashboard,
        Link $link,
        int $sortOrder,
        DateTimeInterface $createdAt
    )
    {
        $this->dashboard = $dashboard;
        $this->link = $link;
        $this->sortOrder = $sortOrder;
        $this->createdAt = $createdAt;
    }

    public function equals(object $entity): bool
    {
        return match (true) {
            !$entity instanceof self => false,
            default => $entity->dashboard->dashboardId->equals($this->dashboard->dashboardId)
                && $entity->link->linkId->equals($this->link->linkId),
        };
    }
}
