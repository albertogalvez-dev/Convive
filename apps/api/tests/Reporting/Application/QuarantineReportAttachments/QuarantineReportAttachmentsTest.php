<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Application\QuarantineReportAttachments;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Application\AttachmentStorage;
use App\Reporting\Application\QuarantinedAttachmentUpload;
use App\Reporting\Application\QuarantineReportAttachments\AttachmentUploadCountExceeded;
use App\Reporting\Application\QuarantineReportAttachments\QuarantineReportAttachments;
use App\Reporting\Application\StoredAttachment;
use App\Reporting\Domain\AttachmentMediaType;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportAttachmentPolicy;
use App\Reporting\Domain\ReportAttachmentQuotaExceeded;
use App\Reporting\Domain\ReportAttachmentRepository;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class QuarantineReportAttachmentsTest extends TestCase
{
    /** @var list<string> */
    private array $sourcePaths = [];

    protected function tearDown(): void
    {
        foreach ($this->sourcePaths as $sourcePath) {
            @unlink($sourcePath);
        }

        parent::tearDown();
    }

    public function testItWritesOnlyQuarantinedMetadataWithinReportCapacity(): void
    {
        $storage = new InMemoryAttachmentStorage();
        $repository = new InMemoryAttachmentRepository();
        $report = $this->createReport();

        $attachments = (new QuarantineReportAttachments($storage, $repository))(
            $report,
            [
                $this->upload(AttachmentMediaType::Pdf),
                $this->upload(AttachmentMediaType::Png),
            ],
        );

        self::assertCount(2, $attachments);
        self::assertCount(2, $repository->attachments);
        self::assertSame(2, $report->attachmentCount());
        self::assertSame(2048, $report->attachmentBytes());
        self::assertSame([], $storage->deletedIds);
    }

    public function testItRejectsMoreFilesThanOneWriteMayContain(): void
    {
        $storage = new InMemoryAttachmentStorage();
        $repository = new InMemoryAttachmentRepository();

        $this->expectException(AttachmentUploadCountExceeded::class);

        (new QuarantineReportAttachments($storage, $repository))(
            $this->createReport(),
            [
                $this->upload(AttachmentMediaType::Pdf),
                $this->upload(AttachmentMediaType::Pdf),
                $this->upload(AttachmentMediaType::Pdf),
                $this->upload(AttachmentMediaType::Pdf),
            ],
        );
    }

    public function testItDeletesPrivateObjectsWhenTheReportQuotaRejectsTheWrite(): void
    {
        $storage = new InMemoryAttachmentStorage();
        $repository = new InMemoryAttachmentRepository();
        $report = $this->createReport();
        $report->reserveAttachmentCapacity(
            ReportAttachmentPolicy::MAXIMUM_ATTACHMENTS_PER_REPORT,
            ReportAttachmentPolicy::MAXIMUM_REPORT_ATTACHMENT_BYTES,
        );

        $this->expectException(ReportAttachmentQuotaExceeded::class);

        try {
            (new QuarantineReportAttachments($storage, $repository))(
                $report,
                [$this->upload(AttachmentMediaType::Pdf)],
            );
        } finally {
            self::assertCount(1, $storage->deletedIds);
            self::assertSame([], $repository->attachments);
        }
    }

    private function upload(AttachmentMediaType $mediaType): QuarantinedAttachmentUpload
    {
        $path = tempnam(sys_get_temp_dir(), 'convive-quarantine-upload-');
        self::assertNotFalse($path);
        file_put_contents($path, 'fictional attachment bytes');
        $this->sourcePaths[] = $path;

        return new QuarantinedAttachmentUpload($path, $mediaType);
    }

    private function createReport(): Report
    {
        return Report::create(
            new Organisation(
                Uuid::v7(),
                'IES Attachment Quarantine',
                PublicReportingIdentifier::generate(),
            ),
            SituationDescription::fromString('A fictional attachment quarantine test.'),
            SituationContext::Digital,
        )->report;
    }
}

final class InMemoryAttachmentStorage implements AttachmentStorage
{
    /** @var list<string> */
    public array $deletedIds = [];

    public function storeQuarantine(Uuid $attachmentId, string $sourcePath): StoredAttachment
    {
        return new StoredAttachment(1024, str_repeat('a', 64));
    }

    public function open(ReportAttachment $attachment)
    {
        $stream = fopen('php://memory', 'rb');

        if ($stream === false) {
            throw new \RuntimeException('The in-memory test stream cannot be opened.');
        }

        return $stream;
    }

    public function promoteToAvailable(ReportAttachment $attachment): void
    {
    }

    public function delete(ReportAttachment $attachment): void
    {
        $this->deletedIds[] = $attachment->id()->toRfc4122();
    }
}

final class InMemoryAttachmentRepository implements ReportAttachmentRepository
{
    /** @var list<ReportAttachment> */
    public array $attachments = [];

    public function save(ReportAttachment $attachment): void
    {
        $this->attachments[] = $attachment;
    }

    public function saveQuarantinedWithReportCapacity(array $attachments): void
    {
        if ($attachments === []) {
            throw new \InvalidArgumentException();
        }

        $report = $attachments[0]->report();
        $report->reserveAttachmentCapacity(
            count($attachments),
            array_sum(array_map(
                static fn (ReportAttachment $attachment): int => $attachment->byteSize(),
                $attachments,
            )),
        );
        array_push($this->attachments, ...$attachments);
    }

    public function findByIdForReport(Uuid $id, Report $report): ?ReportAttachment
    {
        foreach ($this->attachments as $attachment) {
            if ($attachment->id()->equals($id) && $attachment->report()->id()->equals($report->id())) {
                return $attachment;
            }
        }

        return null;
    }

    public function findByReport(Report $report): array
    {
        return array_values(array_filter(
            $this->attachments,
            static fn (ReportAttachment $attachment): bool => $attachment->report()->id()->equals($report->id()),
        ));
    }
}
