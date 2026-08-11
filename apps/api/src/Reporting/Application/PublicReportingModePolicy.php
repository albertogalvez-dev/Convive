<?php

declare(strict_types=1);

namespace App\Reporting\Application;

final readonly class PublicReportingModePolicy
{
    public function __construct(
        private string $configuredMode,
    ) {
    }

    public function mode(): PublicReportingMode
    {
        return PublicReportingMode::tryFrom($this->configuredMode)
            ?? PublicReportingMode::Disabled;
    }

    public function acceptsReporterMutations(): bool
    {
        return $this->mode() === PublicReportingMode::Operational;
    }
}
