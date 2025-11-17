<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

enum TokenType: string implements ValueEqualityInterface
{
    case CLI_TOKEN = "cli";
    case SESSION_TOKEN = "session";
    case REMEMBER_ME_TOKEN = "remember_me";

    public function equals(object $value): bool
    {
        return match (true) {
            !$value instanceof self => false,
            default => $value->value === $this->value,
        };
    }
}
