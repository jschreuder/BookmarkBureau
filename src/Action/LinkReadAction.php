<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

use jschreuder\BookmarkBureau\Entity\Link;
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
    /** @param  OutputSpecInterface<Link> $outputSpec */
    public function __construct(
        private LinkServiceInterface $linkService,
        private InputSpecInterface $inputSpec,
        private OutputSpecInterface $outputSpec,
    ) {}

    #[\Override]
    public function filter(array $rawData): array
    {
        return $this->inputSpec->filter($rawData);
    }

    #[\Override]
    public function validate(array $data): void
    {
        $this->inputSpec->validate($data);
    }

    /** @param array{link_id: string} $data */
    #[\Override]
    public function execute(array $data): array
    {
        $link = $this->linkService->getLink(Uuid::fromString($data["link_id"]));
        return $this->outputSpec->transform($link);
    }
}
