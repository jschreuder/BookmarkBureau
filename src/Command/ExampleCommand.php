<?php declare(strict_types = 1);

namespace jschreuder\BookmarkBureau\Command;

use jschreuder\BookmarkBureau\Service\ExampleService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExampleCommand extends Command
{
    public function __construct(
        private ExampleService $exampleService
    )
    {
        parent::__construct('middle:example');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Example CLI Command')
            ->addArgument('name', InputArgument::OPTIONAL, 'Your name', 'unknown');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln($this->exampleService->getMessage() . ' to you ' . $input->getArgument('name'));
        return 0;
    }
}
