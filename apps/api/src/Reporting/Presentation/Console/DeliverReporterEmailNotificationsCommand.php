<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Console;

use App\Reporting\Application\DeliverReporterEmailNotifications;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:reporter-notifications:deliver', description: 'Deliver bounded reporter email jobs.')]
final class DeliverReporterEmailNotificationsCommand extends Command
{
    public function __construct(private readonly DeliverReporterEmailNotifications $deliver)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum jobs to claim.', '20');
        $this->addOption('watch', null, InputOption::VALUE_NONE, 'Keep polling without rebooting Symfony.');
        $this->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Seconds between watch polls.', '2');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rawLimit = $input->getOption('limit');
        $rawSleep = $input->getOption('sleep');

        if (!is_string($rawLimit) || !ctype_digit($rawLimit) || (int) $rawLimit < 1 || (int) $rawLimit > 100) {
            $output->writeln('<error>The delivery limit must be between 1 and 100.</error>');

            return Command::INVALID;
        }

        if (!is_string($rawSleep) || !ctype_digit($rawSleep) || (int) $rawSleep < 1 || (int) $rawSleep > 60) {
            $output->writeln('<error>The watch sleep must be between 1 and 60 seconds.</error>');

            return Command::INVALID;
        }

        do {
            $count = ($this->deliver)((int) $rawLimit);

            if ($count > 0) {
                $output->writeln(sprintf('Reporter notification delivery completed: %d delivered.', $count));
            }

            if (!$input->getOption('watch')) {
                break;
            }

            sleep((int) $rawSleep);
        } while (true);

        return Command::SUCCESS;
    }
}
