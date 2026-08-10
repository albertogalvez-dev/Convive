<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Infrastructure;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Organisations\Infrastructure\DoctrineOrganisationRepository;
use App\Reporting\Domain\AttachmentMediaType;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportAttachmentPolicy;
use App\Reporting\Domain\ReportAttachmentQuotaExceeded;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use App\Reporting\Infrastructure\DoctrineReportAttachmentRepository;
use App\Reporting\Infrastructure\DoctrineReportRepository;
use App\Tests\Shared\Infrastructure\Persistence\PostgreSqlTestCase;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class DoctrineReportAttachmentRepositoryTest extends PostgreSqlTestCase
{
    private DoctrineOrganisationRepository $organisations;
    private DoctrineReportRepository $reports;
    private DoctrineReportAttachmentRepository $attachments;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisations = new DoctrineOrganisationRepository($this->entityManager);
        $this->reports = new DoctrineReportRepository($this->entityManager);
        $this->attachments = new DoctrineReportAttachmentRepository($this->entityManager);
    }

    public function testItAtomicallyReservesReportCapacityAndPersistsQuarantinedAttachments(): void
    {
        $report = $this->persistReport();
        $first = $this->attachment($report, AttachmentMediaType::Pdf, 1024, 'a');
        $second = $this->attachment($report, AttachmentMediaType::Png, 2048, 'b');

        $this->attachments->saveQuarantinedWithReportCapacity([$first, $second]);
        $this->entityManager->clear();

        $persistedReport = $this->reports->findByPublicReference($report->publicReference());
        self::assertNotNull($persistedReport);
        self::assertSame(2, $persistedReport->attachmentCount());
        self::assertSame(3072, $persistedReport->attachmentBytes());

        $persistedAttachments = $this->attachments->findByReport($persistedReport);
        self::assertCount(2, $persistedAttachments);
        self::assertSame('quarantined', $persistedAttachments[0]->status()->value);
        self::assertSame('quarantined', $persistedAttachments[1]->status()->value);
    }

    public function testItRejectsAnAggregateThatExceedsTheReportCapacity(): void
    {
        $report = $this->persistReport();
        $attachments = [];

        for ($index = 0; $index < ReportAttachmentPolicy::MAXIMUM_ATTACHMENTS_PER_REPORT + 1; ++$index) {
            $attachments[] = $this->attachment(
                $report,
                AttachmentMediaType::Pdf,
                1,
                $this->hashCharacter($index),
            );
        }

        $this->expectException(ReportAttachmentQuotaExceeded::class);

        $this->attachments->saveQuarantinedWithReportCapacity($attachments);
    }

    public function testItFindsOnlyAttachmentsThatNeedPrivateScanWork(): void
    {
        $report = $this->persistReport();
        $attachment = $this->attachment($report, AttachmentMediaType::Pdf, 1024, 'a');
        $this->attachments->saveQuarantinedWithReportCapacity([$attachment]);
        $this->entityManager->clear();

        $awaitingScan = $this->attachments->findAwaitingScan(10);

        self::assertCount(1, $awaitingScan);
        self::assertSame($attachment->id()->toRfc4122(), $awaitingScan[0]->id()->toRfc4122());
    }

    public function testItFindsRejectedAttachmentsForCleanup(): void
    {
        $report = $this->persistReport();
        $attachment = $this->attachment($report, AttachmentMediaType::Pdf, 1024, 'a');
        $this->attachments->saveQuarantinedWithReportCapacity([$attachment]);
        $attachment->reject(new DateTimeImmutable('2026-08-10T20:01:00+00:00'));
        $this->attachments->save($attachment);
        $this->entityManager->clear();

        $candidates = $this->attachments->findForCleanup(
            new DateTimeImmutable('2026-08-10T19:00:00+00:00'),
            new DateTimeImmutable('2026-07-11T20:00:00+00:00'),
            10,
        );

        self::assertCount(1, $candidates);
        self::assertSame('rejected', $candidates[0]->status()->value);
    }

    private function persistReport(): Report
    {
        $organisation = new Organisation(
            Uuid::v7(),
            'IES Attachment Persistence',
            PublicReportingIdentifier::generate(),
        );
        $this->organisations->save($organisation);
        $report = Report::create(
            $organisation,
            SituationDescription::fromString('A fictional attachment persistence test.'),
            SituationContext::Digital,
        )->report;
        $this->reports->save($report);

        return $report;
    }

    private function attachment(
        Report $report,
        AttachmentMediaType $mediaType,
        int $bytes,
        string $hashCharacter,
    ): ReportAttachment {
        return ReportAttachment::quarantine(
            Uuid::v7(),
            $report,
            $mediaType,
            $bytes,
            str_repeat($hashCharacter, 64),
            new DateTimeImmutable('2026-08-10T20:00:00+00:00'),
        );
    }

    private function hashCharacter(int $index): string
    {
        return match ($index % 6) {
            0 => 'a',
            1 => 'b',
            2 => 'c',
            3 => 'd',
            4 => 'e',
            default => 'f',
        };
    }
}
