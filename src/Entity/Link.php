<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

final class Link
{
    public function __construct(
        private readonly UuidInterface $linkId,
        private Value\Url $url,
        private string $title,
        private string $description,
        private string $icon,
        private readonly DateTimeInterface $createdAt,
        private DateTimeInterface $updatedAt
    ) {}

    public function getId(): UuidInterface
    {
        return $this->linkId;
    }

    public function getUrl(): Value\Url
    {
        return $this->url;
    }

    public function setUrl(Value\Url $url): void
    {
        $this->url = $url;
        $this->markAsUpdated();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
        $this->markAsUpdated();
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
        $this->markAsUpdated();
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
        $this->markAsUpdated();
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function markAsUpdated(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
