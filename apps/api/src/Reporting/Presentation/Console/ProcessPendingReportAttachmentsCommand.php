<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Console;

use App\Reporting\Application\ProcessAttachments\ProcessPendingReportAttachments;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:attachments:process-pending',
    description: 'Processes bounded private attachment scan work.',
)]
final class ProcessPendingReportAttachmentsCommand extends Command
{
    public function __construct(
        private readonly ProcessPendingReportAttachments $processAttachments,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'limit',
            null,
            InputOption::VALUE_REQUIRED,
            'Maximum private attachment records to process (1-200).',
            '50',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = $this->parseLimit($input->getOption('limit'));

        if ($limit === null) {
            $output->writeln('<error>The attachment processing limit must be an integer from 1 to 200.</error>');

            return Command::INVALID;
        }

        $processed = ($this->processAttachments)($limit);
        $output->writeln(sprintf('Processed %d private attachment record(s).', $processed));

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
