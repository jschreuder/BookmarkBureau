<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Controller\Action;

use jschreuder\BookmarkBureau\Service\LinkServiceInterface;
use jschreuder\BookmarkBureau\Util\Filter;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

final readonly class DeleteLinkAction implements ActionInterface
{
    public function __construct(private LinkServiceInterface $linkService)
    {
    }

    public function filter(array $rawData): array
    {
        $filtered = [];
        $filtered['id'] = Filter::start($rawData, 'id', '')
            ->string(allowNull: false)->trim()->done();

        return $filtered;
    }

    public function validate(array $data): void
    {
        try {
            Validator::arrayType()
                ->key('id', Validator::notEmpty()->uuid())
                ->assert($data);
        } catch (NestedValidationException $exception) {
            // Get all error messages as an array
            throw new ValidationFailedException($exception->getMessages());
        }
    }

    public function execute(array $data): array
    {
        $linkId = Uuid::fromString($data['id']);
        $this->linkService->deleteLink($linkId);

        return [];
    }
}
