<?php

declare(strict_types=1);

namespace App\Reporting\Application\RevokeReportAccess;

final readonly class RevokeReportAccessCommand
{
    public function __construct(
        public string $capabilityHandle,
    ) {
    }
}
