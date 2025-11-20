<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OutputSpec;

use DateTimeInterface;
use jschreuder\BookmarkBureau\Entity\Category;

final readonly class CategoryOutputSpec implements OutputSpecInterface
{
    use OutputSpecTrait;

    #[\Override]
    public function supports(object $data): bool
    {
        return $data instanceof Category;
    }

    /**
     * @param  Category $category
     */
    #[\Override]
    private function doTransform(object $category): array
    {
        return [
            "id" => $category->categoryId->toString(),
            "dashboard_id" => $category->dashboard->dashboardId->toString(),
            "title" => $category->title->value,
            "color" => $category->color?->value,
            "sort_order" => $category->sortOrder,
            "created_at" => $category->createdAt->format(
                DateTimeInterface::ATOM,
            ),
            "updated_at" => $category->updatedAt->format(
                DateTimeInterface::ATOM,
            ),
        ];
    }
}
