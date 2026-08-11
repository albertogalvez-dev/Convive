<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Application;

use App\Reporting\Application\PublicReportingMode;
use App\Reporting\Application\PublicReportingModePolicy;
use PHPUnit\Framework\TestCase;

final class PublicReportingModePolicyTest extends TestCase
{
    public function testItAcceptsReporterMutationsOnlyInExplicitOperationalMode(): void
    {
        self::assertTrue(
            (new PublicReportingModePolicy(PublicReportingMode::Operational->value))
                ->acceptsReporterMutations(),
        );
        self::assertFalse(
            (new PublicReportingModePolicy(PublicReportingMode::FictionalDemo->value))
                ->acceptsReporterMutations(),
        );
        self::assertFalse(
            (new PublicReportingModePolicy(PublicReportingMode::Disabled->value))
                ->acceptsReporterMutations(),
        );
    }

    public function testItTreatsUnknownConfigurationAsDisabled(): void
    {
        $policy = new PublicReportingModePolicy('typo-or-missing-mode');

        self::assertSame(PublicReportingMode::Disabled, $policy->mode());
        self::assertFalse($policy->acceptsReporterMutations());
    }
}
