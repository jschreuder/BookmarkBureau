<?php declare(strict_types = 1);

namespace jschreuder\BookmarkBureau\Service;

use PDO;

class LinkService
{
    public function __construct(
        private PDO $database
    ) {}

    public function getMessage(): string
    {
        return 'Hello world!';
    }
}
