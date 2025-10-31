<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OutputSpec;

use DateTimeInterface;
use jschreuder\BookmarkBureau\Entity\Favorite;

final readonly class FavoriteOutputSpec implements OutputSpecInterface
{
    use OutputSpecTrait;

    #[\Override]
    public function supports(object $data): bool
    {
        return $data instanceof Favorite;
    }

    /**
     * @param Favorite $favorite
     */
    private function doTransform(object $favorite): array
    {
        return [
            'dashboard_id' => $favorite->dashboard->dashboardId->toString(),
            'link_id' => $favorite->link->linkId->toString(),
            'sort_order' => $favorite->sortOrder,
            'created_at' => $favorite->createdAt->format(DateTimeInterface::ATOM),
        ];
    }
}
