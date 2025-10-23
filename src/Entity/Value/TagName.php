<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Value;

use InvalidArgumentException;

final readonly class TagName
{
    use StringValueTrait;

    public const PATTERN = '/^[a-z0-9\-]+$/';

    public function __construct(string $value)
    {
        $value = trim(strtolower($value)); // Normalize to lowercase
        
        if ($value === '') {
            throw new InvalidArgumentException('Tag name cannot be empty');
        }

        if (mb_strlen($value) > 100) {
            throw new InvalidArgumentException('Tag name cannot exceed 100 characters');
        }

        if (!preg_match(self::PATTERN, $value)) {
            throw new InvalidArgumentException(
                'Tag name can only contain lowercase letters, numbers, and hyphens'
            );
        }
        
        $this->value = $value;
    }
}
