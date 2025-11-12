<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Command\Security;

use jschreuder\BookmarkBureau\Service\RateLimitServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RateLimitCleanupCommand extends Command
{
    public function __construct(
        private RateLimitServiceInterface $rateLimitService,
    ) {
        parent::__construct("security:ratelimit-cleanup");
    }

    protected function configure(): void
    {
        $this->setDescription(
            "Clean up expired rate limiting data (run via cron)",
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        try {
            $output->writeln(
                "<info>Cleaning up expired rate limiting data...</info>",
            );

            $deletedCount = $this->rateLimitService->cleanup();

            if ($deletedCount > 0) {
                $output->writeln(
                    "<info>✓ Deleted {$deletedCount} expired record(s)</info>",
                );
            } else {
                $output->writeln(
                    "<comment>No expired records to delete</comment>",
                );
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(
                "<error>Cleanup error: {$e->getMessage()}</error>",
            );
            return Command::FAILURE;
        }
    }
}
