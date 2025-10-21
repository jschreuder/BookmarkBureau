<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Controller\Action;

use DateTimeInterface;
use jschreuder\BookmarkBureau\Service\LinkServiceInterface;
use Laminas\Filter\StringTrim;
use Laminas\Validator\StringLength;
use Laminas\Validator\Uri;
use jschreuder\Middle\Exception\ValidationFailedException;

final readonly class CreateLinkAction implements ActionInterface
{
    public function __construct(private LinkServiceInterface $linkService)
    {
    }

    public function filter(array $rawData): array
    {
        $trimFilter = new StringTrim();

        $filtered = [];

        // URL: trim whitespace
        $filtered['url'] = $trimFilter->filter($rawData['url'] ?? '');

        // Title: trim whitespace (icon field constraints will be validated later)
        $filtered['title'] = $trimFilter->filter($rawData['title'] ?? '');

        // Description: trim whitespace
        $filtered['description'] = $trimFilter->filter($rawData['description'] ?? '');

        // Icon: trim whitespace if present, set to null if empty
        $icon = $trimFilter->filter($rawData['icon'] ?? '');
        $filtered['icon'] = $icon === '' ? null : $icon;

        return $filtered;
    }

    public function validate(array $data): void
    {
        $errors = [];

        // Validate URL
        $urlValidator = new Uri(['allowRelative' => false]);
        if (!$urlValidator->isValid($data['url'] ?? '')) {
            $errors['url'] = 'URL must be a valid URL';
        }

        // Validate Title
        $titleValidator = new StringLength(['min' => 1, 'max' => 255]);
        if (!$titleValidator->isValid($data['title'] ?? '')) {
            $errors['title'] = 'Title must be between 1 and 255 characters';
        }

        // Validate Description (optional, but cannot be null)
        if (!isset($data['description']) || $data['description'] === null) {
            $errors['description'] = 'Description cannot be null';
        }

        // Validate Icon (optional, but if provided must be non-empty)
        if (isset($data['icon']) && $data['icon'] !== null && $data['icon'] === '') {
            $errors['icon'] = 'Icon cannot be empty if provided';
        }

        if (!empty($errors)) {
            throw new ValidationFailedException($errors);
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
