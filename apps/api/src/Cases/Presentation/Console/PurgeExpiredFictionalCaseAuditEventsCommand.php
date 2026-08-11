<?php

declare(strict_types=1);

namespace App\Cases\Presentation\Console;

use App\Cases\Application\PurgeExpiredFictionalCaseAuditEvents;
use DateTimeImmutable;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:case-audit:clean-fictional',
    description: 'Deletes expired fictional case audit events through the protected retention boundary.',
)]
final class PurgeExpiredFictionalCaseAuditEventsCommand extends Command
{
    public function __construct(private readonly PurgeExpiredFictionalCaseAuditEvents $purgeEvents)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'limit',
            null,
            InputOption::VALUE_REQUIRED,
            'Maximum fictional audit events to clean (1-200).',
            '50',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = $this->parseLimit($input->getOption('limit'));
        if ($limit === null) {
            $output->writeln('<error>The case audit cleanup limit must be an integer from 1 to 200.</error>');

            return Command::INVALID;
        }

        try {
            $cleaned = ($this->purgeEvents)($limit, DateTimeImmutable::createFromTimestamp(microtime(true)));
        } catch (LogicException $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        $output->writeln(sprintf('Cleaned %d expired fictional case audit event(s).', $cleaned));

        return Command::SUCCESS;
    }

    private function parseLimit(mixed $value): ?int
    {
        if (!is_string($value) || !ctype_digit($value)) {
            return null;
        }

        $limit = (int) $value;

        return $limit >= 1 && $limit <= 200 ? $limit : null;
    }
}
