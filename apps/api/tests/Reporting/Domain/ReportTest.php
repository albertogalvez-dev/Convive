<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Domain;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAlreadyReviewed;
use App\Reporting\Domain\ReportReviewReason;
use App\Reporting\Domain\ReportStatus;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final class ReportTest extends TestCase
{
    public function testItCreatesAReportWithTheRequiredInitialState(): void
    {
        $organisation = $this->createOrganisation();
        $description = SituationDescription::fromString(
            'A student is being excluded repeatedly during break time.',
        );

        $creationResult = Report::create(
            $organisation,
            $description,
            SituationContext::InPerson,
        );

        $report = $creationResult->report;

        self::assertInstanceOf(UuidV7::class, $report->id());
        self::assertSame($organisation, $report->organisation());
        self::assertTrue(
            $description->equals(
                $report->situationDescription(),
            ),
        );
        self::assertSame(
            SituationContext::InPerson,
            $report->situationContext(),
        );
        self::assertSame(ReportStatus::Received, $report->status());
    }

    public function testItCreatesSecureAnonymousAccessCredentials(): void
    {
        $creationResult = Report::create(
            $this->createOrganisation(),
            SituationDescription::fromString(
                'Threatening messages have been received through a group chat.',
            ),
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
        $beforeCreation = DateTimeImmutable::createFromTimestamp(
            microtime(true),
        );

        $creationResult = Report::create(
            $this->createOrganisation(),
            SituationDescription::fromString(
                'The situation may be happening both at school and online.',
            ),
            SituationContext::Mixed,
        );

        $afterCreation = DateTimeImmutable::createFromTimestamp(
            microtime(true),
        );
        $createdAt = $creationResult->report->createdAt();

        self::assertSame('+00:00', $createdAt->format('P'));
        self::assertGreaterThanOrEqual($beforeCreation, $createdAt);
        self::assertLessThanOrEqual($afterCreation, $createdAt);
    }

    public function testItRecordsTheInitialReviewExactlyOnce(): void
    {
        $report = Report::create(
            $this->createOrganisation(),
            SituationDescription::fromString(
                'A student is repeatedly excluded during break time.',
            ),
            SituationContext::InPerson,
        )->report;
        $professionalId = Uuid::fromString('0192a5c0-3333-7000-8000-000000000031');
        $reviewedAt = new DateTimeImmutable('2026-08-09T20:00:00+00:00');

        $report->review(
            ReportReviewReason::fromString('Initial safeguarding assessment completed.'),
            $professionalId,
            $reviewedAt,
        );

        self::assertSame(ReportStatus::Reviewed, $report->status());
        self::assertSame(
            'Initial safeguarding assessment completed.',
            $report->reviewReason()?->toString(),
        );
        self::assertTrue($professionalId->equals($report->reviewedByProfessionalId()));
        self::assertSame($reviewedAt, $report->reviewedAt());

        $this->expectException(ReportAlreadyReviewed::class);

        $report->review(
            ReportReviewReason::fromString('A second review must not replace the first.'),
            $professionalId,
            $reviewedAt,
        );
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
