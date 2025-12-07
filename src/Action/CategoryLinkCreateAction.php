<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\Entity\CategoryLink;
use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface;
use jschreuder\BookmarkBureau\Service\CategoryServiceInterface;
use Ramsey\Uuid\Uuid;

/**
 * Add a link to a category. Expects the CategoryLinkInputSpec, but it can be
 * replaced to modify filtering and validation.
 */
final readonly class CategoryLinkCreateAction implements ActionInterface
{
    /** @param  OutputSpecInterface<CategoryLink> $outputSpec */
    public function __construct(
        private CategoryServiceInterface $categoryService,
        private InputSpecInterface $inputSpec,
        private OutputSpecInterface $outputSpec,
    ) {}

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

    /** @param array{category_id: string, link_id: string} $data */
    #[\Override]
    public function execute(array $data): array
    {
        $categoryLink = $this->categoryService->addLinkToCategory(
            categoryId: Uuid::fromString($data["category_id"]),
            linkId: Uuid::fromString($data["link_id"]),
        );
        return $this->outputSpec->transform($categoryLink);
    }
}
