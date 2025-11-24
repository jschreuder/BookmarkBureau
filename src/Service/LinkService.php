<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use DateTimeImmutable;
use jschreuder\BookmarkBureau\Composite\LinkCollection;
use jschreuder\BookmarkBureau\Composite\TagCollection;
use jschreuder\BookmarkBureau\Composite\TagNameCollection;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Entity\Value\Url;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class LinkService implements LinkServiceInterface
{
    public function __construct(
        private readonly LinkRepositoryInterface $linkRepository,
        private readonly LinkServicePipelines $pipelines,
    ) {}

    /**
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function getLink(UuidInterface $linkId): Link
    {
        return $this->pipelines
            ->getLink()
            ->run(
                fn(UuidInterface $lid): Link => $this->linkRepository->findById(
                    $lid,
                ),
                $linkId,
            );
    }

    #[\Override]
    public function createLink(
        string $url,
        string $title,
        string $description = "",
        ?string $icon = null,
    ): Link {
        $newLink = new Link(
            Uuid::uuid4(),
            new Url($url),
            new Title($title),
            $description,
            $icon !== null ? new Icon($icon) : null,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            new TagCollection(),
        );

        return $this->pipelines->createLink()->run(function (Link $link): Link {
            $this->linkRepository->save($link);
            return $link;
        }, $newLink);
    }

    /**
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function updateLink(
        UuidInterface $linkId,
        string $url,
        string $title,
        string $description = "",
        ?string $icon = null,
    ): Link {
        $updatedLink = $this->linkRepository->findById($linkId);
        $updatedLink->url = new Url($url);
        $updatedLink->title = new Title($title);
        $updatedLink->description = $description;
        $updatedLink->icon = $icon !== null ? new Icon($icon) : null;

        return $this->pipelines->updateLink()->run(function (Link $link): Link {
            $this->linkRepository->save($link);
            return $link;
        }, $updatedLink);
    }

    /**
     * @throws LinkNotFoundException when link doesn't exist
     */
    #[\Override]
    public function deleteLink(UuidInterface $linkId): void
    {
        $deleteLink = $this->linkRepository->findById($linkId);
        $this->pipelines->deleteLink()->run(function (Link $link): null {
            $this->linkRepository->delete($link);
            return null;
        }, $deleteLink);
    }

    #[\Override]
    public function searchLinks(string $query, int $limit = 100): LinkCollection
    {
        return $this->pipelines
            ->searchLinks()
            ->run(
                fn(): LinkCollection => $this->linkRepository->search(
                    $query,
                    $limit,
                ),
            );
    }

    #[\Override]
    public function findLinksByTag(string $tagName): LinkCollection
    {
        $searchTagNames = new TagNameCollection(new TagName($tagName));
        return $this->pipelines
            ->findLinksByTag()
            ->run(
                fn(
                    TagNameCollection $tagNames,
                ): LinkCollection => $this->linkRepository->findByTags(
                    $tagNames,
                ),
                $searchTagNames,
            );
    }

    #[\Override]
    public function listLinks(int $limit = 100, int $offset = 0): LinkCollection
    {
        return $this->pipelines
            ->listLinks()
            ->run(fn() => $this->linkRepository->findAll($limit, $offset));
    }
}
