<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Application\ProcessAttachments;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Application\AttachmentScanner;
use App\Reporting\Application\AttachmentScanVerdict;
use App\Reporting\Application\AttachmentStorage;
use App\Reporting\Application\ProcessAttachments\CleanExpiredReportAttachments;
use App\Reporting\Application\ProcessAttachments\ProcessReportAttachment;
use App\Reporting\Application\StoredAttachment;
use App\Reporting\Domain\AttachmentMediaType;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportAttachmentRepository;
use App\Reporting\Domain\ReportAttachmentStatus;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use App\Shared\Infrastructure\Logging\SecurityEventLogger;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Uid\Uuid;

final class AttachmentLifecycleTest extends TestCase
{
    public function testOnlyACleanScannerVerdictMakesThePrivateObjectAvailable(): void
    {
        $attachment = $this->quarantinedAttachment();
        $storage = new LifecycleStorage();
        $repository = new LifecycleRepository();
        $processor = new ProcessReportAttachment(
            $storage,
            new FixedAttachmentScanner(AttachmentScanVerdict::Clean),
            $repository,
            $this->securityEvents(),
        );

        $processor($attachment, new DateTimeImmutable('2026-08-10T21:00:00+00:00'));

        self::assertSame(ReportAttachmentStatus::Available, $attachment->status());
        self::assertTrue($storage->promoted);
        self::assertGreaterThanOrEqual(2, $repository->saveCount);
    }

    public function testAnUnavailableScannerLeavesTheAttachmentUnreadable(): void
    {
        $attachment = $this->quarantinedAttachment();
        $storage = new LifecycleStorage();
        $processor = new ProcessReportAttachment(
            $storage,
            new FixedAttachmentScanner(AttachmentScanVerdict::Unavailable),
            new LifecycleRepository(),
            $this->securityEvents(),
        );

        $processor($attachment, new DateTimeImmutable('2026-08-10T21:00:00+00:00'));

        self::assertSame(ReportAttachmentStatus::Scanning, $attachment->status());
        self::assertFalse($storage->promoted);
        self::assertFalse($attachment->isAvailable());
    }

    public function testAnExpiredScanWindowRejectsWithoutOpeningThePrivateObject(): void
    {
        $attachment = $this->quarantinedAttachment();
        $attachment->beginScanning(new DateTimeImmutable('2026-08-10T20:00:00+00:00'));
        $storage = new LifecycleStorage();
        $processor = new ProcessReportAttachment(
            $storage,
            new FixedAttachmentScanner(AttachmentScanVerdict::Clean),
            new LifecycleRepository(),
            $this->securityEvents(),
        );

        $processor($attachment, new DateTimeImmutable('2026-08-10T20:31:00+00:00'));

        self::assertSame(ReportAttachmentStatus::Rejected, $attachment->status());
        self::assertSame(0, $storage->openCount);
    }

    public function testCleanupDeletesExpiredAvailableEvidence(): void
    {
        $attachment = $this->quarantinedAttachment(
            new DateTimeImmutable('-31 days'),
        );
        $attachment->beginScanning(new DateTimeImmutable('-31 days'));
        $attachment->markAvailable(new DateTimeImmutable('-31 days'));
        $storage = new LifecycleStorage();
        $repository = new LifecycleRepository();
        $repository->cleanupCandidates = [$attachment];

        $cleaned = (new CleanExpiredReportAttachments(
            $storage,
            $repository,
            $this->securityEvents(),
        ))(20);

        self::assertSame(1, $cleaned);
        self::assertTrue($storage->deleted);
        self::assertSame(ReportAttachmentStatus::Deleted, $attachment->status());
    }

    private function quarantinedAttachment(?DateTimeImmutable $createdAt = null): ReportAttachment
    {
        $report = Report::create(
            new Organisation(
                Uuid::v7(),
                'IES Attachment Lifecycle',
                PublicReportingIdentifier::generate(),
            ),
            SituationDescription::fromString('A fictional attachment lifecycle test.'),
            SituationContext::Digital,
        )->report;

        return ReportAttachment::quarantine(
            Uuid::v7(),
            $report,
            AttachmentMediaType::Pdf,
            1024,
            str_repeat('a', 64),
            $createdAt ?? new DateTimeImmutable('2026-08-10T20:00:00+00:00'),
        );
    }

    private function securityEvents(): SecurityEventLogger
    {
        return new SecurityEventLogger(new NullLogger());
    }
}

final class FixedAttachmentScanner implements AttachmentScanner
{
    public function __construct(private AttachmentScanVerdict $verdict)
    {
    }

    public function scan($content): AttachmentScanVerdict
    {
        return $this->verdict;
    }
}

final class LifecycleStorage implements AttachmentStorage
{
    public bool $promoted = false;
    public bool $deleted = false;
    public int $openCount = 0;

    public function storeQuarantine(Uuid $attachmentId, string $sourcePath): StoredAttachment
    {
        return new StoredAttachment(1024, str_repeat('a', 64));
    }

    public function open(ReportAttachment $attachment)
    {
        ++$this->openCount;
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new \RuntimeException('The test scanner stream cannot be opened.');
        }

        fwrite($stream, '%PDF-1.7');
        rewind($stream);

        return $stream;
    }

    public function promoteToAvailable(ReportAttachment $attachment): void
    {
        $this->promoted = true;
    }

    public function delete(ReportAttachment $attachment): void
    {
        $this->deleted = true;
    }

    public function deleteQuarantineObjectsOlderThan(DateTimeImmutable $deadline): int
    {
        return 0;
    }
}

final class LifecycleRepository implements ReportAttachmentRepository
{
    public int $saveCount = 0;

    /** @var list<ReportAttachment> */
    public array $cleanupCandidates = [];

    public function save(ReportAttachment $attachment): void
    {
        ++$this->saveCount;
    }

    public function saveQuarantinedWithReportCapacity(array $attachments): void
    {
    }

    public function findByIdForReport(Uuid $id, Report $report): ?ReportAttachment
    {
        return null;
    }

    public function findByReport(Report $report): array
    {
        return [];
    }

    public function findAwaitingScan(int $limit): array
    {
        return [];
    }

    public function findForCleanup(
        DateTimeImmutable $quarantineDeadline,
        DateTimeImmutable $availableDeadline,
        int $limit,
    ): array {
        return $this->cleanupCandidates;
    }
}
