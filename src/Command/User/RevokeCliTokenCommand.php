<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Command\User;

use InvalidArgumentException;
use jschreuder\BookmarkBureau\Repository\JwtJtiRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RevokeCliTokenCommand extends Command
{
    public function __construct(
        private JwtJtiRepositoryInterface $jwtJtiRepository,
    ) {
        parent::__construct("user:revoke-cli-token");
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription(
            "Revoke a CLI token by invalidating its JTI (prevents further use)",
        )->addArgument(
            "jti",
            InputArgument::REQUIRED,
            "The JWT JTI (token ID) to revoke",
        );
    }

    #[\Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $jtiString = $input->getArgument("jti") ?? "";
        if (!\is_string($jtiString)) {
            throw new InvalidArgumentException("JTI must be a string");
        }

        try {
            $jti = Uuid::fromString($jtiString);

            if (!$this->jwtJtiRepository->hasJti($jti)) {
                $output->writeln(
                    "<comment>JTI not found in whitelist (already revoked or invalid)</comment>",
                );
                return Command::SUCCESS;
            }

            $this->jwtJtiRepository->deleteJti($jti);

            $output->writeln("<info>CLI token revoked successfully:</info>");
            $output->writeln("");
            $output->writeln($jtiString);
            $output->writeln("");
            $output->writeln(
                "<comment>This token will no longer be accepted for authentication.</comment>",
            );

            return Command::SUCCESS;
        } catch (InvalidUuidStringException) {
            $output->writeln(
                "<error>Invalid JTI format. Must be a valid UUID.</error>",
            );
        } catch (InvalidArgumentException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
        }

        return Command::FAILURE;
    }
}
