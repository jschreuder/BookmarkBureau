<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

enum TokenType: string
{
    case CLI_TOKEN = "cli";
    case SESSION_TOKEN = "session";
    case REMEMBER_ME_TOKEN = "remember_me";
}
