<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Util;

final class Filter
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
        $this->value = $function($this->value);
        return $this;
    }

    public function string(bool $allowNull = true): self
    {
        $this->value =
            !$allowNull || $this->value !== null ? (string) $this->value : null;
        return $this;
    }

    public function int(bool $allowNull = true): self
    {
        $this->value =
            !$allowNull || $this->value !== null ? (int) $this->value : null;
        return $this;
    }

    public function float(bool $allowNull = true): self
    {
        $this->value =
            !$allowNull || $this->value !== null ? (float) $this->value : null;
        return $this;
    }

    public function bool(bool $allowNull = true): self
    {
        $this->value =
            !$allowNull || $this->value !== null ? (bool) $this->value : null;
        return $this;
    }

    public function uppercase(): self
    {
        $this->value = \is_string($this->value)
            ? strtoupper($this->value)
            : $this->value;
        return $this;
    }

    public function lowercase(): self
    {
        $this->value = \is_string($this->value)
            ? strtolower($this->value)
            : $this->value;
        return $this;
    }

    public function trim(): self
    {
        $this->value = \is_string($this->value)
            ? trim($this->value)
            : $this->value;
        return $this;
    }

    public function striptags(): self
    {
        $this->value = \is_string($this->value)
            ? strip_tags($this->value)
            : $this->value;
        return $this;
    }

    public function htmlspecialchars(
        int $flags = ENT_QUOTES | ENT_HTML5,
        string $encoding = "UTF-8",
    ): self {
        $this->value = \is_string($this->value)
            ? htmlspecialchars($this->value, $flags, $encoding)
            : $this->value;
        return $this;
    }

    public function done(): mixed
    {
        return $this->value;
    }
}
