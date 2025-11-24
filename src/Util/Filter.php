<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Util;

final readonly class Filter
{
    private function __construct(private mixed $value) {}

    /** @param array<string, mixed> $data */
    public static function start(
        array $data,
        string $key,
        mixed $default = null,
    ): self {
        return new self($data[$key] ?? $default);
    }

    public function do(callable $function): self
    {
        $value = $function($this->value);
        return new self($value);
    }

    public function string(bool $allowNull = true): self
    {
        $value =
            !$allowNull || $this->value !== null ? (string) $this->value : null;
        return new self($value);
    }

    public function int(bool $allowNull = true): self
    {
        $value =
            !$allowNull || $this->value !== null ? (int) $this->value : null;
        return new self($value);
    }

    public function float(bool $allowNull = true): self
    {
        $value =
            !$allowNull || $this->value !== null ? (float) $this->value : null;
        return new self($value);
    }

    public function bool(bool $allowNull = true): self
    {
        $value =
            !$allowNull || $this->value !== null ? (bool) $this->value : null;
        return new self($value);
    }

    public function uppercase(): self
    {
        $value = \is_string($this->value)
            ? strtoupper($this->value)
            : $this->value;
        return new self($value);
    }

    public function lowercase(): self
    {
        $value = \is_string($this->value)
            ? strtolower($this->value)
            : $this->value;
        return new self($value);
    }

    public function trim(): self
    {
        $value = \is_string($this->value) ? trim($this->value) : $this->value;
        return new self($value);
    }

    public function striptags(): self
    {
        $value = \is_string($this->value)
            ? strip_tags($this->value)
            : $this->value;
        return new self($value);
    }

    public function htmlspecialchars(
        int $flags = ENT_QUOTES | ENT_HTML5,
        string $encoding = "UTF-8",
    ): self {
        $value = \is_string($this->value)
            ? htmlspecialchars($this->value, $flags, $encoding)
            : $this->value;
        return new self($value);
    }

    public function done(): mixed
    {
        return $this->value;
    }
}
