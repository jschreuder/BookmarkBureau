<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Controller\Action;

use DateTimeInterface;
use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\Service\LinkServiceInterface;

final readonly class CreateLinkAction implements ActionInterface
{
    public function __construct(
        private LinkServiceInterface $linkService,
        private InputSpecInterface $inputSpec
    ) {}

    public function filter(array $rawData): array
    {
        $fields = array_diff($this->inputSpec->getAvailableFields(), ['id']);
        return $this->inputSpec->filter($rawData, $fields);
    }

    public function validate(array $data): void
    {
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
