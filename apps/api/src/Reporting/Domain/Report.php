<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use App\Organisations\Domain\Organisation;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Random\RandomException;
use RuntimeException;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'reports')]
#[ORM\Index(
    name: 'idx_reports_professional_inbox',
    columns: ['organisation_id', 'status', 'created_at', 'id'],
)]
class Report
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Organisation::class)]
    #[ORM\JoinColumn(
        name: 'organisation_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private Organisation $organisation;

    #[ORM\Column(type: Types::TEXT)]
    private string $situationDescription;

    #[ORM\Column(
        type: Types::STRING,
        length: 20,
        enumType: SituationContext::class,
    )]
    private SituationContext $situationContext;

    #[ORM\Column(
        type: Types::STRING,
        length: 20,
        enumType: ReportStatus::class,
    )]
    private ReportStatus $status;

    #[ORM\Column(type: Types::STRING, length: 32, unique: true)]
    private string $publicReference;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        unique: true,
    )]
    private string $accessSecretHash;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reviewReason = null;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $reviewedByProfessionalId = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $reviewedAt = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $attachmentCount = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $attachmentBytes = 0;

    #[ORM\Version]
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1])]
    private int $version = 1;

    private function __construct(
        Uuid $id,
        Organisation $organisation,
        SituationDescription $situationDescription,
        SituationContext $situationContext,
        string $publicReference,
        string $accessSecretHash,
        DateTimeImmutable $createdAt,
    ) {
        $this->id = $id;
        $this->organisation = $organisation;
        $this->situationDescription = $situationDescription->toString();
        $this->situationContext = $situationContext;
        $this->status = ReportStatus::Received;
        $this->publicReference = $publicReference;
        $this->accessSecretHash = $accessSecretHash;
        $this->createdAt = $createdAt;
    }

    public static function create(
        Organisation $organisation,
        SituationDescription $situationDescription,
        SituationContext $situationContext,
    ): ReportCreationResult {
        try {
            $publicReferenceBytes = random_bytes(10);
        } catch (RandomException $exception) {
            throw new RuntimeException(
                'Unable to generate a secure public report reference.',
                previous: $exception,
            );
        }

        $publicReference = bin2hex($publicReferenceBytes);
        $publicReference = strtoupper($publicReference);
        $accessSecret = ReportAccessSecret::generate();

        $report = new self(
            Uuid::v7(),
            $organisation,
            $situationDescription,
            $situationContext,
            $publicReference,
            $accessSecret->lookupHash(),
            DateTimeImmutable::createFromTimestamp(microtime(true)),
        );

        return new ReportCreationResult(
            $report,
            $accessSecret->reveal(),
        );
    }

    public function verifyAccessSecret(string $plainAccessSecret): bool
    {
        return hash_equals(
            $this->accessSecretHash,
            hash('sha256', $plainAccessSecret),
        );
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function organisation(): Organisation
    {
        return $this->organisation;
    }

    public function situationDescription(): SituationDescription
    {
        return SituationDescription::fromString(
            $this->situationDescription,
        );
    }

    public function situationContext(): SituationContext
    {
        return $this->situationContext;
    }

    public function status(): ReportStatus
    {
        return $this->status;
    }

    public function publicReference(): string
    {
        return $this->publicReference;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function review(
        ReportReviewReason $reason,
        Uuid $reviewedByProfessionalId,
        DateTimeImmutable $reviewedAt,
    ): void {
        if ($this->status === ReportStatus::Reviewed) {
            throw new ReportAlreadyReviewed();
        }

        $this->status = ReportStatus::Reviewed;
        $this->reviewReason = $reason->toString();
        $this->reviewedByProfessionalId = $reviewedByProfessionalId;
        $this->reviewedAt = $reviewedAt;
    }

    public function reviewReason(): ?ReportReviewReason
    {
        return $this->reviewReason === null
            ? null
            : ReportReviewReason::fromString($this->reviewReason);
    }

    public function reviewedByProfessionalId(): ?Uuid
    {
        return $this->reviewedByProfessionalId;
    }

    public function reviewedAt(): ?DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function reserveAttachmentCapacity(int $count, int $bytes): void
    {
        if ($count < 1 || $bytes < 1) {
            throw new \InvalidArgumentException('Attachment capacity must be positive.');
        }

        if (
            $this->attachmentCount + $count > ReportAttachmentPolicy::MAXIMUM_ATTACHMENTS_PER_REPORT
            || $this->attachmentBytes + $bytes > ReportAttachmentPolicy::MAXIMUM_REPORT_ATTACHMENT_BYTES
        ) {
            throw new ReportAttachmentQuotaExceeded();
        }

        $this->attachmentCount += $count;
        $this->attachmentBytes += $bytes;
    }

    public function releaseAttachmentCapacity(int $count, int $bytes): void
    {
        if ($count < 1 || $bytes < 1 || $count > $this->attachmentCount || $bytes > $this->attachmentBytes) {
            throw new \LogicException('Attachment capacity cannot become negative.');
        }

        $this->attachmentCount -= $count;
        $this->attachmentBytes -= $bytes;
    }

    public function attachmentCount(): int
    {
        return $this->attachmentCount;
    }

    public function attachmentBytes(): int
    {
        return $this->attachmentBytes;
    }
}
