<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use DateTimeImmutable;
use jschreuder\BookmarkBureau\Collection\LinkCollection;
use jschreuder\BookmarkBureau\Collection\TagNameCollection;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Entity\Value\Url;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use jschreuder\BookmarkBureau\Service\UnitOfWork\UnitOfWorkInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class LinkService implements LinkServiceInterface
{
    public function __construct(
        private readonly LinkRepositoryInterface $linkRepository,
        private readonly UnitOfWorkInterface $unitOfWork
    ) {
    }

    /**
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function getLink(UuidInterface $linkId): Link
    {
        return $this->linkRepository->findById($linkId);
    }

    #[\Override]
    public function createLink(
        string $url,
        string $title,
        string $description = '',
        ?string $icon = null
    ): Link {
        return $this->unitOfWork->transactional(function () use ($url, $title, $description, $icon): Link {
            $link = new Link(
                Uuid::uuid4(),
                new Url($url),
                new Title($title),
                $description,
                $icon !== null ? new Icon($icon) : null,
                new DateTimeImmutable(),
                new DateTimeImmutable()
            );

            $this->linkRepository->save($link);

            return $link;
        });
    }

    /**
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function updateLink(
        UuidInterface $linkId,
        string $url,
        string $title,
        string $description = '',
        ?string $icon = null
    ): Link {
        return $this->unitOfWork->transactional(function () use ($linkId, $url, $title, $description, $icon): Link {
            $link = $this->linkRepository->findById($linkId);

            $link->url = new Url($url);
            $link->title = new Title($title);
            $link->description = $description;
            $link->icon = $icon !== null ? new Icon($icon) : null;

            $this->linkRepository->save($link);

            return $link;
        });
    }

    /**
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function deleteLink(UuidInterface $linkId): void
    {
        $this->unitOfWork->transactional(function () use ($linkId): void {
            $link = $this->linkRepository->findById($linkId);
            $this->linkRepository->delete($link);
        });
    }

    #[\Override]
    public function searchLinks(string $query, int $limit = 100): LinkCollection
    {
        return $this->linkRepository->search($query, $limit);
    }

    #[\Override]
    public function findLinksByTag(string $tagName): LinkCollection
    {
        $tagNames = new TagNameCollection(new TagName($tagName));
        return $this->linkRepository->findByTags($tagNames);
    }

    #[\Override]
    public function listLinks(int $limit = 100, int $offset = 0): LinkCollection
    {
        return $this->linkRepository->findAll($limit, $offset);
    }
}
