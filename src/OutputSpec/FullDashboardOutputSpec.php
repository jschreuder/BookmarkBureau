<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OutputSpec;

use jschreuder\BookmarkBureau\Collection\DashboardWithCategoriesAndFavorites;

/**
 * Output specification for transforming a complete dashboard view into array format
 *
 * This OutputSpec composes multiple other OutputSpecs to transform a DashboardWithCategoriesAndFavorites
 * object (which contains a dashboard, categories with links, and favorites) into a nested array structure
 * suitable for JSON serialization.
 *
 * This is an example of OutputSpec composition pattern, where a complex object is transformed
 * by delegating to simpler, single-responsibility OutputSpecs.
 */
final readonly class FullDashboardOutputSpec implements OutputSpecInterface
{
    use OutputSpecTrait;

    public function __construct(
        private DashboardOutputSpec $dashboardOutputSpec,
        private CategoryOutputSpec $categoryOutputSpec,
        private LinkOutputSpec $linkOutputSpec,
    ) {}

    #[\Override]
    public function supports(object $data): bool
    {
        return $data instanceof DashboardWithCategoriesAndFavorites;
    }

    /**
     * @param DashboardWithCategoriesAndFavorites $dashboardView
     */
    private function doTransform(object $dashboardView): array
    {
        // Transform the base dashboard
        $dashboardArray = $this->dashboardOutputSpec->transform(
            $dashboardView->dashboard,
        );

        // Transform categories with their links
        $categoriesArray = [];
        foreach ($dashboardView->categories as $categoryWithLinks) {
            $categoryArray = $this->categoryOutputSpec->transform(
                $categoryWithLinks->category,
            );

            // Transform links within the category
            $linksArray = [];
            foreach ($categoryWithLinks->links as $link) {
                $linksArray[] = $this->linkOutputSpec->transform($link);
            }
            $categoryArray["links"] = $linksArray;

            $categoriesArray[] = $categoryArray;
        }

        // Transform favorites
        $favoritesArray = [];
        foreach ($dashboardView->favorites as $favorite) {
            $favoritesArray[] = $this->linkOutputSpec->transform($favorite);
        }

        // Return structure matching the FullDashboard interface
        return [
            "dashboard" => $dashboardArray,
            "categories" => $categoriesArray,
            "favorites" => $favoritesArray,
        ];
    }
}
