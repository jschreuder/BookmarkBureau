<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Repository;

use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;
use jschreuder\BookmarkBureau\Exception\RepositoryStorageException;

final class FileJwtJtiRepository implements JwtJtiRepositoryInterface
{
    /**
     * File format: one JTI per line as CSV: jti,user_id,created_at
     *
     * @throws RepositoryStorageException if path is not writable
     */
    public function __construct(private readonly string $filePath)
    {
        $dir = dirname($filePath);
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new RepositoryStorageException(
                "Directory does not exist or is not writable: {$dir}",
            );
        }
        // Only check file writeability if it already exists
        if (file_exists($filePath) && !is_writable($filePath)) {
            throw new RepositoryStorageException(
                "JWT JTI file is not writable: {$filePath}",
            );
        }
    }

    /**
     * @throws RepositoryStorageException on storage failure
     */
    #[\Override]
    public function saveJti(
        UuidInterface $jti,
        UuidInterface $userId,
        DateTimeInterface $createdAt,
    ): void {
        try {
            $line = \sprintf(
                "%s,%s,%d\n",
                $jti->toString(),
                $userId->toString(),
                $createdAt->getTimestamp(),
            );

            if (
                file_put_contents(
                    $this->filePath,
                    $line,
                    FILE_APPEND | LOCK_EX,
                ) === false
            ) {
                throw new RepositoryStorageException(
                    "Failed to write JWT JTI to file: {$this->filePath}",
                );
            }
        } catch (RepositoryStorageException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new RepositoryStorageException(
                "Failed to save JWT JTI: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    #[\Override]
    public function hasJti(UuidInterface $jti): bool
    {
        try {
            $jtiString = $jti->toString();
            $searchPrefix = "{$jtiString},";

            // Return false if file does not exist or cannot be opened
            if (
                !file_exists($this->filePath) ||
                !($handle = fopen($this->filePath, "r"))
            ) {
                return false;
            }

            // Read file line by line and check for JTI
            try {
                while (($line = fgets($handle)) !== false) {
                    if (strpos($line, $searchPrefix) === 0) {
                        return true;
                    }
                }
                return false;
            } finally {
                fclose($handle);
            }
        } catch (\Throwable $e) {
            throw new RepositoryStorageException(
                "Failed to check JWT JTI: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * @throws RepositoryStorageException on storage failure
     */
    #[\Override]
    public function deleteJti(UuidInterface $jti): void
    {
        try {
            if (!file_exists($this->filePath)) {
                return;
            }

            $jtiString = $jti->toString();
            $searchPrefix = "{$jtiString},";

            // Read all lines except the matching JTI
            $handle = fopen($this->filePath, "r");
            if ($handle === false) {
                return;
            }

            $lines = [];
            try {
                while (($line = fgets($handle)) !== false) {
                    if (strpos($line, $searchPrefix) !== 0) {
                        $lines[] = $line;
                    }
                }
            } finally {
                fclose($handle);
            }

            // Write back the filtered lines
            if (empty($lines)) {
                // If no lines remain, remove the file
                if (file_exists($this->filePath)) {
                    unlink($this->filePath);
                }
            } else {
                // Write the remaining lines
                if (
                    file_put_contents(
                        $this->filePath,
                        implode("", $lines),
                        LOCK_EX,
                    ) === false
                ) {
                    throw new RepositoryStorageException(
                        "Failed to write JWT JTI file: {$this->filePath}",
                    );
                }
            }
        } catch (RepositoryStorageException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new RepositoryStorageException(
                "Failed to delete JWT JTI: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }
}
