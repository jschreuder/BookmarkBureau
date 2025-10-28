<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\OutputSpec\OutputSpecInterface;
use jschreuder\BookmarkBureau\Service\LinkServiceInterface;
use Ramsey\Uuid\Uuid;

/**
 * Expects the IdInputSpec, but it can be replaced to modify filtering and
 * validation.
 */
final readonly class LinkReadAction implements ActionInterface
{
    public function __construct(
        private LinkServiceInterface $linkService,
        private InputSpecInterface $inputSpec,
        private OutputSpecInterface $outputSpec
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
        $link = $this->linkService->getLink(Uuid::fromString($data['id']));
        return $this->outputSpec->transform($link);
    }
}
