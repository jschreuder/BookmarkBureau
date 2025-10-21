<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Controller\Action;

use DateTimeInterface;
use jschreuder\BookmarkBureau\Service\DashboardServiceInterface;
use jschreuder\BookmarkBureau\Util\Filter;
use jschreuder\Middle\Exception\ValidationFailedException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

final readonly class CreateDashboardAction implements ActionInterface
{
    public function __construct(private DashboardServiceInterface $dashboardService)
    {
    }

    public function filter(array $rawData): array
    {
        $filtered = [];
        $filtered['title'] = Filter::start($rawData, 'title', '')
            ->string(allowNull: false)->trim()->done();
        $filtered['description'] = Filter::start($rawData, 'description', '')
            ->string(allowNull: false)->trim()->done();
        $filtered['icon'] = Filter::start($rawData, 'icon', null)
            ->string()->trim()->done();

        return $filtered;
    }

    public function validate(array $data): void
    {
        try {
            Validator::arrayType()
                ->key('title', Validator::notEmpty()->length(1, 256))
                ->key('description', Validator::optional(Validator::stringType()))
                ->key('icon', Validator::optional(Validator::stringType()))
                ->assert($data);
        } catch (NestedValidationException $exception) {
            // Get all error messages as an array
            throw new ValidationFailedException($exception->getMessages());
        }
    }

    public function execute(array $data): array
    {
        $dashboard = $this->dashboardService->createDashboard(
            title: $data['title'],
            description: $data['description'],
            icon: $data['icon']
        );
        return [
            'id' => $dashboard->dashboardId->toString(),
            'title' => $dashboard->title->value,
            'description' => $dashboard->description,
            'icon' => $dashboard->icon?->value,
            'created_at' => $dashboard->createdAt->format(DateTimeInterface::ATOM),
            'updated_at' => $dashboard->updatedAt->format(DateTimeInterface::ATOM),
        ];
    }
}
