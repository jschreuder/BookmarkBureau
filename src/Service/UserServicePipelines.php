<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Service;

use jschreuder\BookmarkBureau\OperationPipeline\NoPipeline;
use jschreuder\BookmarkBureau\OperationPipeline\PipelineInterface;

final readonly class UserServicePipelines
{
    public function __construct(
        private PipelineInterface $default = new NoPipeline(),
        private ?PipelineInterface $getUser = null,
        private ?PipelineInterface $getUserByEmail = null,
        private ?PipelineInterface $listAllUsers = null,
        private ?PipelineInterface $createUser = null,
        private ?PipelineInterface $deleteUser = null,
        private ?PipelineInterface $changePassword = null,
        private ?PipelineInterface $enableTotp = null,
        private ?PipelineInterface $disableTotp = null,
    ) {}

    public function getUser(): PipelineInterface
    {
        return $this->getUser ?? $this->default;
    }

    public function getUserByEmail(): PipelineInterface
    {
        return $this->getUserByEmail ?? $this->default;
    }

    public function listAllUsers(): PipelineInterface
    {
        return $this->listAllUsers ?? $this->default;
    }

    public function createUser(): PipelineInterface
    {
        return $this->createUser ?? $this->default;
    }

    public function deleteUser(): PipelineInterface
    {
        return $this->deleteUser ?? $this->default;
    }

    public function changePassword(): PipelineInterface
    {
        return $this->changePassword ?? $this->default;
    }

    public function enableTotp(): PipelineInterface
    {
        return $this->enableTotp ?? $this->default;
    }

    public function disableTotp(): PipelineInterface
    {
        return $this->disableTotp ?? $this->default;
    }
}
