<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Domain;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportStatus;
use App\Reporting\Domain\SituationContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final class ReportTest extends TestCase
{
    public function testItCreatesAReportWithTheRequiredInitialState(): void
    {
        $organisation = $this->createOrganisation();

        $creationResult = Report::create(
            $organisation,
            'A student is being excluded repeatedly during break time.',
            SituationContext::InPerson,
        );

        $report = $creationResult->report;

        self::assertInstanceOf(UuidV7::class, $report->id());
        self::assertSame($organisation, $report->organisation());
        self::assertSame(
            'A student is being excluded repeatedly during break time.',
            $report->situationDescription(),
        );
        self::assertSame(SituationContext::InPerson, $report->situationContext());
        self::assertSame(ReportStatus::Received, $report->status());
    }

    public function testItCreatesSecureAnonymousAccessCredentials(): void
    {
        $creationResult = Report::create(
            $this->createOrganisation(),
            'Threatening messages have been received through a group chat.',
            SituationContext::Digital,
        );

        self::assertMatchesRegularExpression(
            '/^[A-F0-9]{20}$/',
            $creationResult->report->publicReference(),
        );
        self::assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $creationResult->plainAccessSecret,
        );
        self::assertTrue(
            $creationResult->report->verifyAccessSecret(
                $creationResult->plainAccessSecret,
            ),
        );
        self::assertFalse(
            $creationResult->report->verifyAccessSecret(
                'an-incorrect-access-secret',
            ),
        );
    }

    public function testItRecordsTheCreationTimeInUtc(): void
    {
        $utc = new \DateTimeZone('UTC');
        $beforeCreation = new \DateTimeImmutable('now', $utc);

        $creationResult = Report::create(
            $this->createOrganisation(),
            'The situation may be happening both at school and online.',
            SituationContext::Mixed,
        );

        $afterCreation = new \DateTimeImmutable('now', $utc);
        $createdAt = $creationResult->report->createdAt();

        self::assertSame('UTC', $createdAt->getTimezone()->getName());
        self::assertGreaterThanOrEqual($beforeCreation, $createdAt);
        self::assertLessThanOrEqual($afterCreation, $createdAt);
    }

    private function createOrganisation(): Organisation
    {
        return new Organisation(
            Uuid::fromString('0192a5c0-1111-7000-8000-000000000001'),
            'IES Valle Sereno',
            PublicReportingIdentifier::fromString(
                'ORG_7M4K9T2W6N8Q3R5X',
            ),
        );
    }
}
