<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'report_attachments')]
#[ORM\UniqueConstraint(
    name: 'uniq_report_attachments_storage_key',
    columns: ['storage_key'],
)]
#[ORM\Index(
    name: 'idx_report_attachments_report_status_created',
    columns: ['report_id', 'status', 'created_at', 'id'],
)]
class ReportAttachment
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Report::class)]
    #[ORM\JoinColumn(
        name: 'report_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private Report $report;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        enumType: AttachmentMediaType::class,
    )]
    private AttachmentMediaType $mediaType;

    #[ORM\Column(type: Types::INTEGER)]
    private int $byteSize;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $contentHash;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $storageKey;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        enumType: ReportAttachmentStatus::class,
    )]
    private ReportAttachmentStatus $status;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $scanStartedAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $deletionRequestedAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    private function __construct(
        Uuid $id,
        Report $report,
        AttachmentMediaType $mediaType,
        int $byteSize,
        string $contentHash,
        DateTimeImmutable $createdAt,
    ) {
        if ($byteSize < 1 || $byteSize > ReportAttachmentPolicy::MAXIMUM_FILE_BYTES) {
            throw new InvalidArgumentException('The attachment byte size is invalid.');
        }

        if (!preg_match('/^[a-f0-9]{64}$/D', $contentHash)) {
            throw new InvalidArgumentException('The attachment content hash is invalid.');
        }

        $this->id = $id;
        $this->report = $report;
        $this->mediaType = $mediaType;
        $this->byteSize = $byteSize;
        $this->contentHash = $contentHash;
        $this->storageKey = self::quarantineStorageKey($id);
        $this->status = ReportAttachmentStatus::Quarantined;
        $this->createdAt = $createdAt;
    }

    public static function quarantine(
        Uuid $id,
        Report $report,
        AttachmentMediaType $mediaType,
        int $byteSize,
        string $contentHash,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            $id,
            $report,
            $mediaType,
            $byteSize,
            $contentHash,
            $createdAt,
        );
    }

    public function beginScanning(DateTimeImmutable $startedAt): void
    {
        if ($this->status !== ReportAttachmentStatus::Quarantined) {
            throw new \LogicException('Only quarantined attachments may be scanned.');
        }

        $this->status = ReportAttachmentStatus::Scanning;
        $this->scanStartedAt = $startedAt;
    }

    public function markAvailable(DateTimeImmutable $resolvedAt): void
    {
        if ($this->status !== ReportAttachmentStatus::Scanning) {
            throw new \LogicException('Only scanning attachments may become available.');
        }

        $this->status = ReportAttachmentStatus::Available;
        $this->storageKey = self::availableStorageKey($this->id);
        $this->resolvedAt = $resolvedAt;
    }

    public function reject(DateTimeImmutable $resolvedAt): void
    {
        if (!in_array($this->status, [ReportAttachmentStatus::Quarantined, ReportAttachmentStatus::Scanning], true)) {
            throw new \LogicException('Only unresolved attachments may be rejected.');
        }

        $this->status = ReportAttachmentStatus::Rejected;
        $this->resolvedAt = $resolvedAt;
    }

    public function requestDeletion(DateTimeImmutable $requestedAt): void
    {
        if ($this->status === ReportAttachmentStatus::Deleted) {
            return;
        }

        $this->status = ReportAttachmentStatus::DeletionPending;
        $this->deletionRequestedAt = $requestedAt;
    }

    public function markDeleted(DateTimeImmutable $deletedAt): void
    {
        if ($this->status !== ReportAttachmentStatus::DeletionPending) {
            throw new \LogicException('Only deletion-pending attachments may be deleted.');
        }

        $this->status = ReportAttachmentStatus::Deleted;
        $this->deletedAt = $deletedAt;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function report(): Report
    {
        return $this->report;
    }

    public function mediaType(): AttachmentMediaType
    {
        return $this->mediaType;
    }

    public function byteSize(): int
    {
        return $this->byteSize;
    }

    public function contentHash(): string
    {
        return $this->contentHash;
    }

    public function storageKey(): string
    {
        return $this->storageKey;
    }

    public function status(): ReportAttachmentStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function scanStartedAt(): ?DateTimeImmutable
    {
        return $this->scanStartedAt;
    }

    public function resolvedAt(): ?DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function deletionRequestedAt(): ?DateTimeImmutable
    {
        return $this->deletionRequestedAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isAvailable(): bool
    {
        return $this->status === ReportAttachmentStatus::Available;
    }

    public static function quarantineStorageKey(Uuid $id): string
    {
        return 'quarantine/'.$id->toRfc4122();
    }

    public static function availableStorageKey(Uuid $id): string
    {
        return 'available/'.$id->toRfc4122();
    }
}
