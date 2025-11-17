<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

final class Dashboard implements EntityEqualityInterface
{

    public readonly UuidInterface $dashboardId;

    public Value\Title $title {
        set {
            $this->title = $value;
            $this->markAsUpdated();
        }
    }

    public string $description {
        set {
            $this->description = $value;
            $this->markAsUpdated();
        }
    }

    public ?Value\Icon $icon {
        set {
            $this->icon = $value;
            $this->markAsUpdated();
        }
    }

    public readonly DateTimeInterface $createdAt;

    public private(set) DateTimeInterface $updatedAt;

    public function __construct(
        UuidInterface $dashboardId,
        Value\Title $title,
        string $description,
        ?Value\Icon $icon,
        DateTimeInterface $createdAt,
        DateTimeInterface $updatedAt
    )
    {
        $this->dashboardId = $dashboardId;
        $this->title = $title;
        $this->description = $description;
        $this->icon = $icon;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function markAsUpdated(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function equals(object $entity): bool
    {
        return match (true) {
            !$entity instanceof self => false,
            default => $entity->dashboardId->equals($this->dashboardId),
        };
    }
}
