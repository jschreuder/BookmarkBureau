<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Controller\Action;

use DateTimeInterface;
use jschreuder\BookmarkBureau\Service\LinkServiceInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

final readonly class CreateLinkAction implements ActionInterface
{
    public function __construct(private LinkServiceInterface $linkService)
    {
    }

    public function filter(array $rawData): array
    {
        $filtered = [];
        $filtered['url'] = trim(strval($rawData['url'] ?? ''));
        $filtered['title'] = trim(strval($rawData['title'] ?? ''));
        $filtered['description'] = trim(strval($rawData['description'] ?? ''));
        $icon = trim(strval($rawData['icon'] ?? ''));
        $filtered['icon'] = empty($icon) ? null : strval($icon);

        return $filtered;
    }

    public function validate(array $data): void
    {
        try {
            Validator::arrayType()
                ->key('url', Validator::notEmpty()->url())
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
        $link = $this->linkService->createLink(
            url: $data['url'], 
            title: $data['title'], 
            description: $data['description'], 
            icon: $data['icon']
        );
        return [
            'id' => $link->linkId->toString(),
            'url' => $link->url->value,
            'title' => $link->title->value,
            'description' => $link->description,
            'icon' => $link->icon?->value,
            'created_at' => $link->createdAt->format(DateTimeInterface::ATOM),
            'updated_at' => $link->updatedAt->format(DateTimeInterface::ATOM),
        ];
    }
}
