<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\Composite\UserCollection;
use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;
use jschreuder\BookmarkBureau\OperationPipeline\NoPipeline;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;
use Ramsey\Uuid\UuidInterface;

final readonly class UserServicePipelines
{
    /**
     * @param PipelineInterface<UuidInterface, User> $getUser
     * @param PipelineInterface<Email, User> $getUserByEmail
     * @param PipelineInterface<null, UserCollection> $listAllUsers
     * @param PipelineInterface<User, User> $createUser
     * @param PipelineInterface<User, null> $changePassword
     * @param PipelineInterface<User, null> $enableTotp
     * @param PipelineInterface<User, null> $disableTotp
     * @param PipelineInterface<User, null> $deleteUser
     */
    public function __construct(
        private PipelineInterface $default = new NoPipeline(),
        private ?PipelineInterface $getUser = null,
        private ?PipelineInterface $getUserByEmail = null,
        private ?PipelineInterface $listAllUsers = null,
        private ?PipelineInterface $createUser = null,
        private ?PipelineInterface $changePassword = null,
        private ?PipelineInterface $enableTotp = null,
        private ?PipelineInterface $disableTotp = null,
        private ?PipelineInterface $deleteUser = null,
    ) {}

    /** @return PipelineInterface<UuidInterface, User> */
    public function getUser(): PipelineInterface
    {
        return $this->getUser ?? $this->default;
    }

    /** @return PipelineInterface<Email, User> */
    public function getUserByEmail(): PipelineInterface
    {
        return $this->getUserByEmail ?? $this->default;
    }

    /** @return PipelineInterface<null, UserCollection> */
    public function listAllUsers(): PipelineInterface
    {
        return $this->listAllUsers ?? $this->default;
    }

    /** @return PipelineInterface<User, User> */
    public function createUser(): PipelineInterface
    {
        return $this->createUser ?? $this->default;
    }

    /** @return PipelineInterface<User, null> */
    public function changePassword(): PipelineInterface
    {
        return $this->changePassword ?? $this->default;
    }

    /** @return PipelineInterface<User, TotpSecret> */
    public function enableTotp(): PipelineInterface
    {
        return $this->enableTotp ?? $this->default;
    }

    /** @return PipelineInterface<User, null> */
    public function disableTotp(): PipelineInterface
    {
        return $this->disableTotp ?? $this->default;
    }

    /** @return PipelineInterface<User, null> */
    public function deleteUser(): PipelineInterface
    {
        return $this->deleteUser ?? $this->default;
    }
}
