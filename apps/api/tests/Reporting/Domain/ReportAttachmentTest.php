<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Domain;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Domain\AttachmentMediaType;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportAttachmentPolicy;
use App\Reporting\Domain\ReportAttachmentStatus;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final class ReportAttachmentTest extends TestCase
{
    public function testItCreatesPrivateQuarantinedMetadataWithoutAClientFilename(): void
    {
        $createdAt = new DateTimeImmutable('2026-08-10T19:00:00+00:00');
        $attachment = ReportAttachment::quarantine(
            Uuid::v7(),
            $this->createReport(),
            AttachmentMediaType::Pdf,
            1024,
            str_repeat('a', 64),
            $createdAt,
        );

        self::assertInstanceOf(UuidV7::class, $attachment->id());
        self::assertSame(AttachmentMediaType::Pdf, $attachment->mediaType());
        self::assertSame(1024, $attachment->byteSize());
        self::assertSame(ReportAttachmentStatus::Quarantined, $attachment->status());
        self::assertSame($createdAt, $attachment->createdAt());
        self::assertStringStartsWith('quarantine/', $attachment->storageKey());
        self::assertStringNotContainsString('report', $attachment->storageKey());
        self::assertFalse($attachment->isAvailable());
    }

    public function testItRequiresScanningBeforeMakingAnAttachmentAvailable(): void
    {
        $attachment = ReportAttachment::quarantine(
            Uuid::v7(),
            $this->createReport(),
            AttachmentMediaType::Png,
            2048,
            str_repeat('b', 64),
            new DateTimeImmutable('2026-08-10T19:00:00+00:00'),
        );

        $this->expectException(\LogicException::class);

        $attachment->markAvailable(
            new DateTimeImmutable('2026-08-10T19:01:00+00:00'),
        );
    }

    public function testItMovesOnlyAScannedAttachmentToTheAvailableNamespace(): void
    {
        $attachment = ReportAttachment::quarantine(
            Uuid::v7(),
            $this->createReport(),
            AttachmentMediaType::Jpeg,
            2048,
            str_repeat('c', 64),
            new DateTimeImmutable('2026-08-10T19:00:00+00:00'),
        );
        $attachment->beginScanning(
            new DateTimeImmutable('2026-08-10T19:00:30+00:00'),
        );
        $attachment->markAvailable(
            new DateTimeImmutable('2026-08-10T19:01:00+00:00'),
        );

        self::assertSame(AttachmentMediaType::Jpeg, $attachment->mediaType());
        self::assertSame(ReportAttachmentStatus::Available, $attachment->status());
        self::assertStringStartsWith('available/', $attachment->storageKey());
        self::assertTrue($attachment->isAvailable());
    }

    public function testItRejectsOversizedOrMalformedAttachmentMetadata(): void
    {
        $report = $this->createReport();

        $this->expectException(InvalidArgumentException::class);

        ReportAttachment::quarantine(
            Uuid::v7(),
            $report,
            AttachmentMediaType::Pdf,
            ReportAttachmentPolicy::MAXIMUM_FILE_BYTES + 1,
            'not-a-sha256',
            new DateTimeImmutable(),
        );
    }

    private function createReport(): Report
    {
        return Report::create(
            new Organisation(
                \Symfony\Component\Uid\Uuid::v7(),
                'IES Attachment Boundary',
                PublicReportingIdentifier::generate(),
            ),
            SituationDescription::fromString(
                'A fictional attachment boundary is being tested.',
            ),
            SituationContext::Digital,
        )->report;
    }
}
