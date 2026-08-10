<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Console;

use App\Reporting\Application\ProcessAttachments\CleanExpiredReportAttachments;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:attachments:clean-expired',
    description: 'Deletes expired private attachment bytes and metadata.',
)]
final class CleanExpiredReportAttachmentsCommand extends Command
{
    public function __construct(
        private readonly CleanExpiredReportAttachments $cleanAttachments,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'limit',
            null,
            InputOption::VALUE_REQUIRED,
            'Maximum private attachment records to clean (1-200).',
            '50',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = $this->parseLimit($input->getOption('limit'));

        if ($limit === null) {
            $output->writeln('<error>The attachment cleanup limit must be an integer from 1 to 200.</error>');

            return Command::INVALID;
        }

        $cleaned = ($this->cleanAttachments)($limit);
        $output->writeln(sprintf('Cleaned %d private attachment record(s).', $cleaned));

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
