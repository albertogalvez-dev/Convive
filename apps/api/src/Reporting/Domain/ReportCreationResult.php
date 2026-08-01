<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

final readonly class ReportCreationResult
{
    public function __construct(
        public Report $report,
        public string $plainAccessSecret,
    ) {
    }
}
