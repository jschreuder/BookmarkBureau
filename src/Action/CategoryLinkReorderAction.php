<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\Composite\LinkCollection;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Exception\LinkNotFoundException;
use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface;
use jschreuder\BookmarkBureau\Repository\LinkRepositoryInterface;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use Ramsey\Uuid\Uuid;

/**
 * Handles bulk reordering of links within a category via PUT request.
 * Expects ReorderCategoryLinksInputSpec, but it can be replaced to modify filtering and validation.
 */
final readonly class CategoryLinkReorderAction implements ActionInterface
{
    /** @param  OutputSpecInterface<Link> $outputSpec */
    public function __construct(
        private CategoryServiceInterface $categoryService,
        private LinkRepositoryInterface $linkRepository,
        private InputSpecInterface $inputSpec,
        private OutputSpecInterface $outputSpec,
    ) {}

    #[\Override]
    public function getAttributeKeysForData(): array
    {
        return ["category_id"];
    }

    #[\Override]
    public function filter(array $rawData): array
    {
        return $this->inputSpec->filter($rawData);
    }

    #[\Override]
    public function validate(array $data): void
    {
        $this->inputSpec->validate($data);
    }

    /** @param array{category_id: string, links: array<int, array{link_id: string, sort_order: int}>} $data */
    #[\Override]
    public function execute(array $data): array
    {
        $categoryId = Uuid::fromString($data["category_id"]);

        // Get current links for the category and create a map by link_id for quick lookup
        $currentLinks = $this->linkRepository->listForCategoryId($categoryId);
        $linksMap = [];
        foreach ($currentLinks as $link) {
            $linksMap[$link->linkId->toString()] = $link;
        }

        // Collect valid links with their sort_order, throw if link not in category
        $validLinks = [];
        foreach ($data["links"] as $linkData) {
            $linkId = $linkData["link_id"];
            if (!isset($linksMap[$linkId])) {
                throw LinkNotFoundException::forId(Uuid::fromString($linkId));
            }
            $validLinks[] = [
                "link" => $linksMap[$linkId],
                "sort_order" => $linkData["sort_order"],
            ];
        }

        // Sort links by sort_order
        usort($validLinks, fn($a, $b) => $a["sort_order"] <=> $b["sort_order"]);

        // Extract just the link objects in the sorted order
        $reorderedLinks = array_map(fn($item) => $item["link"], $validLinks);

        // Reorder the links in the category
        $this->categoryService->reorderLinksInCategory(
            $categoryId,
            new LinkCollection(...$reorderedLinks),
        );

        // Transform each link to array
        return [
            "links" => array_map(
                $this->outputSpec->transform(...),
                $reorderedLinks,
            ),
        ];
    }
}
