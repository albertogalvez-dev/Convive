<?php

declare(strict_types=1);

namespace App\Reporting\Application\VerifyReportAccess;

final readonly class VerifyReportAccessCommand
{
    public function __construct(
        public string $accessSecret,
        public ?string $previousCapabilityHandle = null,
    ) {
    }
}
