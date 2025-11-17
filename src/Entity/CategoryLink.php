<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity;

use DateTimeInterface;

final class CategoryLink implements EntityEqualityInterface
{

    public readonly Category $category;

    public readonly Link $link;

    public int $sortOrder {
        set {
            $this->sortOrder = $value;
        }
    }

    public readonly DateTimeInterface $createdAt;

    public function __construct(
        Category $category,
        Link $link,
        int $sortOrder,
        DateTimeInterface $createdAt
    )
    {
        $this->category = $category;
        $this->link = $link;
        $this->sortOrder = $sortOrder;
        $this->createdAt = $createdAt;
    }

    public function equals(object $entity): bool
    {
        return match (true) {
            !$entity instanceof self => false,
            default => $entity->category->categoryId->equals($this->category->categoryId)
                && $entity->link->linkId->equals($this->link->linkId),
        };
    }
}
