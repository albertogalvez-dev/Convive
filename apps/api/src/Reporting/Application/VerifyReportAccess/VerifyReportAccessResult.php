<?php

declare(strict_types=1);

namespace App\Reporting\Application\VerifyReportAccess;

use DateTimeImmutable;

final readonly class VerifyReportAccessResult
{
    public function __construct(
        public string $plainCapabilityHandle,
        public DateTimeImmutable $absoluteExpiresAt,
    ) {
    }
}
