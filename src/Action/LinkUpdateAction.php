<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use DateTimeInterface;
use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\Service\LinkServiceInterface;
use Ramsey\Uuid\Uuid;

/**
 * Expects the LinkInputSpec, but it can be replaced to modify filtering and 
 * validation.
 */
final readonly class LinkUpdateAction implements ActionInterface
{
    public function __construct(
        private LinkServiceInterface $linkService,
        private InputSpecInterface $inputSpec
    ) {}

    public function filter(array $rawData): array
    {
        return $this->inputSpec->filter($rawData);
    }

    public function validate(array $data): void
    {
        $this->inputSpec->validate($data);
    }

    public function execute(array $data): array
    {
        $linkId = Uuid::fromString($data['id']);
        $link = $this->linkService->updateLink(
            linkId: $linkId,
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
