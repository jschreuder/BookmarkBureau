<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

final class Category implements EntityEqualityInterface
{

    public readonly UuidInterface $categoryId;

    public readonly Dashboard $dashboard;

    public Value\Title $title {
        set {
            $this->title = $value;
            $this->markAsUpdated();
        }
    }

    public ?Value\HexColor $color {
        set {
            $this->color = $value;
            $this->markAsUpdated();
        }
    }

    public int $sortOrder {
        set {
            $this->sortOrder = $value;
            $this->markAsUpdated();
        }
    }

    public readonly DateTimeInterface $createdAt;

    public private(set) DateTimeInterface $updatedAt;

    public function __construct(
        UuidInterface $categoryId,
        Dashboard $dashboard,
        Value\Title $title,
        ?Value\HexColor $color,
        int $sortOrder,
        DateTimeInterface $createdAt,
        DateTimeInterface $updatedAt
    )
    {
        $this->categoryId = $categoryId;
        $this->dashboard = $dashboard;
        $this->title = $title;
        $this->color = $color;
        $this->sortOrder = $sortOrder;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function markAsUpdated(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    #[\Override]
    public function equals(object $entity): bool
    {
        return match (true) {
            !$entity instanceof self => false,
            default => $entity->categoryId->equals($this->categoryId),
        };
    }
}
