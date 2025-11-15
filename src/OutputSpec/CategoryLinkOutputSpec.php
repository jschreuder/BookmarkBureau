<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OutputSpec;

use DateTimeInterface;
use jschreuder\BookmarkBureau\Entity\CategoryLink;

final readonly class CategoryLinkOutputSpec implements OutputSpecInterface
{
    use OutputSpecTrait;

    #[\Override]
    public function supports(object $data): bool
    {
        return $data instanceof CategoryLink;
    }

    /**
     * @param CategoryLink $categoryLink
     */
    private function doTransform(object $categoryLink): array
    {
        return [
            "category_id" => $categoryLink->category->categoryId->toString(),
            "link_id" => $categoryLink->link->linkId->toString(),
            "sort_order" => $categoryLink->sortOrder,
            "created_at" => $categoryLink->createdAt->format(
                DateTimeInterface::ATOM,
            ),
        ];
    }
}
