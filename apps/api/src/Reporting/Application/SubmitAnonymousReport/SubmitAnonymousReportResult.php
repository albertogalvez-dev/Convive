<?php

declare(strict_types=1);

namespace App\Reporting\Application\SubmitAnonymousReport;

use App\Reporting\Domain\ReportStatus;
use DateTimeImmutable;

final readonly class SubmitAnonymousReportResult
{
    public function __construct(
        public string $publicReference,
        public string $plainAccessSecret,
        public ReportStatus $status,
        public DateTimeImmutable $createdAt,
    ) {
    }
}
