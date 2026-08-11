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
}
