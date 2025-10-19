<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

final class Dashboard
{

    public readonly UuidInterface $dashboardId;

    public string $title {
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

    public ?string $icon {
        set {
            $this->icon = $value;
            $this->markAsUpdated();
        }
    }

    public readonly DateTimeInterface $createdAt;

    public private(set) DateTimeInterface $updatedAt;

    public function __construct(
        UuidInterface $dashboardId,
        string $title,
        string $description,
        ?string $icon,
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
}
