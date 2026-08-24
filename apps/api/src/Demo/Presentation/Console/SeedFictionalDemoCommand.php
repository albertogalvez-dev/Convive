<?php

declare(strict_types=1);

namespace App\Demo\Presentation\Console;

use App\Demo\Application\FictionalDemoDatasetConflict;
use App\Demo\Application\SeedFictionalDemo;
use App\Demo\Domain\FictionalDemoDataset;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:demo:seed',
    description: 'Create or restore the isolated fictional demonstration dataset.',
)]
final class SeedFictionalDemoCommand extends Command
{
    public function __construct(
        private readonly SeedFictionalDemo $seeder,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
        #[Autowire('%env(bool:APP_DEMO_MODE)%')]
        private readonly bool $demoMode,
        #[Autowire('%env(DEMO_PROFESSIONAL_PASSWORD)%')]
        private readonly string $professionalPassword,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'reset',
                null,
                InputOption::VALUE_NONE,
                'Remove all reports belonging to the reserved demo organisation before reseeding.',
            )
            ->addOption(
                'confirm-reset',
                null,
                InputOption::VALUE_REQUIRED,
                'Required reset confirmation token; see the operations runbook.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!in_array($this->environment, ['prod', 'test'], true)) {
            $output->writeln('<error>This command is supported only in the prod fictional-demo environment.</error>');

            return self::FAILURE;
        }

        if (!$this->demoMode) {
            $output->writeln('<error>Fictional demo mode is disabled. Set APP_DEMO_MODE=1 deliberately.</error>');

            return self::FAILURE;
        }

        if (mb_strlen($this->professionalPassword) < 20) {
            $output->writeln('<error>DEMO_PROFESSIONAL_PASSWORD must be a secret of at least 20 characters.</error>');

            return self::FAILURE;
        }

        $reset = $input->getOption('reset') === true;

        if ($reset && $input->getOption('confirm-reset') !== FictionalDemoDataset::RESET_CONFIRMATION) {
            $output->writeln('<error>Reset refused: the exact documented confirmation token is required.</error>');

            return self::FAILURE;
        }

        try {
            $result = $this->seeder->seed($this->professionalPassword, $reset);
        } catch (FictionalDemoDatasetConflict $exception) {
            $output->writeln('<error>Demo seed refused: '.$exception->getMessage().'</error>');

            return self::FAILURE;
        }

        $output->writeln(sprintf(
            '<info>Fictional demo %s: %d organisation, %d professionals, %d reports, %d conversation entries, %d cases, %d assignments and %d involved people.</info>',
            $result->reset ? 'restored' : 'seeded',
            $result->organisations,
            $result->professionals,
            $result->reports,
            $result->conversationEntries,
            $result->managedCases,
            $result->caseAssignments,
            $result->caseInvolvedPeople,
        ));
        $output->writeln('Public reporting identifier: '.FictionalDemoDataset::PUBLIC_REPORTING_IDENTIFIER);
        $output->writeln('No credentials were printed.');

        return self::SUCCESS;
    }
}
