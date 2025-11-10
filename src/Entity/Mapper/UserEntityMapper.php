<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Entity\Mapper;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\HashedPassword;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;
use jschreuder\BookmarkBureau\Util\SqlFormat;

final readonly class UserEntityMapper implements EntityMapperInterface
{
    use EntityMapperTrait;

    private const array FIELDS = [
        "user_id",
        "email",
        "password_hash",
        "totp_secret",
        "created_at",
        "updated_at",
    ];

    #[\Override]
    public function getFields(): array
    {
        return self::FIELDS;
    }

    #[\Override]
    public function supports(object $entity): bool
    {
        return $entity instanceof User;
    }

    #[\Override]
    private function doMapToEntity(array $data): object
    {
        return new User(
            userId: Uuid::fromBytes($data["user_id"]),
            email: new Email($data["email"]),
            passwordHash: new HashedPassword($data["password_hash"]),
            totpSecret: !\is_null($data["totp_secret"])
                ? new TotpSecret($data["totp_secret"])
                : null,
            createdAt: new DateTimeImmutable($data["created_at"]),
            updatedAt: new DateTimeImmutable($data["updated_at"]),
        );
    }

    #[\Override]
    private function doMapToRow(object $entity): array
    {
        /** @var User $entity */
        return [
            "user_id" => $entity->userId->getBytes(),
            "email" => (string) $entity->email,
            "password_hash" => $entity->passwordHash->value,
            "totp_secret" => $entity->totpSecret?->value,
            "created_at" => $entity->createdAt->format(SqlFormat::TIMESTAMP),
            "updated_at" => $entity->updatedAt->format(SqlFormat::TIMESTAMP),
        ];
    }
}
