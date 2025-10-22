<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Controller\Action;

use DateTimeInterface;
use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\Service\LinkServiceInterface;

/**
 * Expects the LinkInputSpec, but it can be replaced to modify filtering and 
 * validation.
 */
final readonly class CreateLinkAction implements ActionInterface
{
    public function __construct(
        private LinkServiceInterface $linkService,
        private InputSpecInterface $inputSpec
    ) {}

    public function filter(array $rawData): array
    {
        // Create operations need all fields except 'id', since it doesn't exist yet
        $fields = array_diff($this->inputSpec->getAvailableFields(), ['id']);
        return $this->inputSpec->filter($rawData, $fields);
    }

    public function validate(array $data): void
    {
        // Create operations need all fields except 'id', since it doesn't exist yet
        $fields = array_diff($this->inputSpec->getAvailableFields(), ['id']);
        $this->inputSpec->validate($data, $fields);
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
