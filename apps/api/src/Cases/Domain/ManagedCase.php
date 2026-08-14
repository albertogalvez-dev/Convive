<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use App\Organisations\Domain\Organisation;
use App\Professionals\Domain\Professional;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'managed_cases')]
#[ORM\Index(name: 'idx_managed_case_operational_updated', columns: ['operational_updated_at', 'id'])]
class ManagedCase
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Organisation::class)]
    #[ORM\JoinColumn(name: 'organisation_id', referencedColumnName: 'id', nullable: false)]
    private Organisation $organisation;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'created_by_professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $createdBy;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $operationalUpdatedAt;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: CaseStatus::class)]
    private CaseStatus $status;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: CaseModality::class)]
    private CaseModality $modality;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $statusReason = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $statusEvidence = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $statusChangedAt = null;

    public function __construct(
        Uuid $id,
        Organisation $organisation,
        Professional $createdBy,
        DateTimeImmutable $createdAt,
        CaseModality $modality,
    ) {
        $this->id = $id;
        $this->organisation = $organisation;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
        $this->operationalUpdatedAt = $createdAt;
        $this->status = CaseStatus::Assessment;
        $this->modality = $modality;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function organisation(): Organisation
    {
        return $this->organisation;
    }

    public function createdBy(): Professional
    {
        return $this->createdBy;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function operationalUpdatedAt(): DateTimeImmutable
    {
        return $this->operationalUpdatedAt;
    }

    public function recordOperationalActivity(DateTimeImmutable $occurredAt): void
    {
        if ($occurredAt < $this->createdAt) {
            throw new \LogicException('Case activity cannot predate case creation.');
        }

        if ($occurredAt > $this->operationalUpdatedAt) {
            $this->operationalUpdatedAt = $occurredAt;
        }
    }

    public function status(): CaseStatus
    {
        return $this->status;
    }

    public function modality(): CaseModality
    {
        return $this->modality;
    }

    public function transitionTo(CaseStatus $status, string $reason, string $evidence, DateTimeImmutable $now): void
    {
        if ($now < $this->createdAt || $status === $this->status || !self::permitsTransition($this->status, $status)) {
            throw new \LogicException('The requested case lifecycle transition is not permitted.');
        }

        $this->status = $status;
        $this->statusReason = self::requiredRecord($reason, 'reason');
        $this->statusEvidence = self::requiredRecord($evidence, 'evidence');
        $this->statusChangedAt = $now;
        $this->recordOperationalActivity($now);
    }

    public function statusReason(): ?string { return $this->statusReason; }
    public function statusEvidence(): ?string { return $this->statusEvidence; }
    public function statusChangedAt(): ?DateTimeImmutable { return $this->statusChangedAt; }

    private static function permitsTransition(CaseStatus $from, CaseStatus $to): bool
    {
        return match ($from) {
            CaseStatus::Assessment => $to === CaseStatus::Active || $to === CaseStatus::Closed,
            CaseStatus::Active => $to === CaseStatus::Closed,
            CaseStatus::Closed => $to === CaseStatus::Active,
        };
    }

    private static function requiredRecord(string $value, string $field): string
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value) > 500) {
            throw new \InvalidArgumentException(sprintf('The lifecycle %s must contain between 1 and 500 characters.', $field));
        }

        return $value;
    }
}
