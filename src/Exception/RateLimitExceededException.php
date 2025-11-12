<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Exception;

use RuntimeException;

final class RateLimitExceededException extends RuntimeException
{
    public function __construct(
        private readonly ?string $blockedUsername = null,
        private readonly ?string $blockedIp = null,
        private readonly ?\DateTimeInterface $expiresAt = null,
    ) {
        $message = "Rate limit exceeded. ";

        if ($this->blockedUsername && $this->blockedIp) {
            $message .= "Username and IP address are blocked.";
        } elseif ($this->blockedUsername) {
            $message .= "Username is blocked.";
        } elseif ($this->blockedIp) {
            $message .= "IP address is blocked.";
        } else {
            $message .= "Too many failed login attempts.";
        }

        if ($this->expiresAt) {
            $message .=
                " Try again after " .
                $this->expiresAt->format("Y-m-d H:i:s") .
                ".";
        }

        parent::__construct($message);
    }

    public function getBlockedUsername(): ?string
    {
        return $this->blockedUsername;
    }

    public function getBlockedIp(): ?string
    {
        return $this->blockedIp;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function getRetryAfterSeconds(): ?int
    {
        if (!$this->expiresAt) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $diff = $this->expiresAt->getTimestamp() - $now->getTimestamp();

        return max(0, $diff);
    }
}
