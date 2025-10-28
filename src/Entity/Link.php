<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

final class Link
{

    public readonly UuidInterface $linkId;

    public Value\Url $url {
        set {
            $this->url = $value;
            $this->markAsUpdated();
        }
    }

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
        UuidInterface $linkId,
        Value\Url $url,
        Value\Title $title,
        string $description,
        ?Value\Icon $icon,
        DateTimeInterface $createdAt,
        DateTimeInterface $updatedAt
    )
    {
        $this->linkId = $linkId;
        $this->url = $url;
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
