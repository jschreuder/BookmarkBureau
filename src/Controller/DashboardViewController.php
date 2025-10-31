<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Controller;

use jschreuder\BookmarkBureau\OutputSpec\DashboardWithCategoriesAndFavoritesOutputSpec;
use jschreuder\BookmarkBureau\Response\ResponseTransformerInterface;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;
use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Respect\Validation\Validator as v;

/**
 * Controller for retrieving a complete dashboard with all its related data
 *
 * This controller handles the complex operation of fetching a dashboard,
 * its categories with their links, and favorites, then rendering them
 * as a structured JSON response using OutputSpecs for serialization.
 */
final readonly class DashboardViewController implements
    ControllerInterface,
    RequestFilterInterface,
    RequestValidatorInterface
{
    public function __construct(
        private DashboardServiceInterface $dashboardService,
        private ResponseTransformerInterface $responseTransformer,
        private DashboardWithCategoriesAndFavoritesOutputSpec $outputSpec,
    ) {}

    #[\Override]
    public function filterRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        // Extract UUID from route attributes
        $id = $request->getAttribute('id');
        if (!is_null($id)) {
            return $request->withParsedBody(['id' => $id]);
        }

        return $request->withParsedBody([]);
    }

    #[\Override]
    public function validateRequest(ServerRequestInterface $request): void
    {
        $data = (array) $request->getParsedBody();

        // Validate that ID is present and is a valid UUID
        if (!isset($data['id'])) {
            throw new \InvalidArgumentException('Dashboard ID is required');
        }

        if (!v::uuid()->validate($data['id'])) {
            throw new \InvalidArgumentException('Invalid dashboard ID format');
        }
    }

    #[\Override]
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $dashboardId = \Ramsey\Uuid\Uuid::fromString($data['id']);

        // Fetch the complete dashboard view (dashboard + categories with links + favorites)
        $dashboardView = $this->dashboardService->getDashboardView($dashboardId);

        // Transform using the composite OutputSpec
        $dashboardArray = $this->outputSpec->transform($dashboardView);

        // Return the response
        return $this->responseTransformer->transform(
            data: [
                'success' => true,
                'data' => $dashboardArray
            ],
            statusCode: 200
        );
    }
}
