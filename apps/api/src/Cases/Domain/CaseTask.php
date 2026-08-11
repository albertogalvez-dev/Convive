<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use App\Professionals\Domain\Professional;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'case_tasks')]
#[ORM\Index(name: 'idx_case_task_case_status_due', columns: ['case_id', 'status', 'due_at'])]
#[ORM\Index(name: 'idx_case_task_owner_status_due', columns: ['owner_professional_id', 'status', 'due_at'])]
class CaseTask
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: ManagedCase::class)]
    #[ORM\JoinColumn(name: 'case_id', referencedColumnName: 'id', nullable: false)]
    private ManagedCase $managedCase;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'owner_professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $owner;

    #[ORM\ManyToOne(targetEntity: WorkflowSourceVersion::class)]
    #[ORM\JoinColumn(name: 'source_version_id', referencedColumnName: 'id', nullable: false)]
    private WorkflowSourceVersion $source;

    #[ORM\Column(type: Types::STRING, length: 40, enumType: CaseProtocolStage::class)]
    private CaseProtocolStage $stage;

    #[ORM\Column(type: Types::STRING, length: 30, enumType: CaseTaskKind::class)]
    private CaseTaskKind $kind;

    #[ORM\Column(type: Types::STRING, length: 160)]
    private string $title;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $dueAt;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: CaseTaskStatus::class)]
    private CaseTaskStatus $status;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'created_by_professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $createdBy;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'resolved_by_professional_id', referencedColumnName: 'id', nullable: true)]
    private ?Professional $resolvedBy = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $notApplicableReason = null;

    public function __construct(
        Uuid $id,
        ManagedCase $managedCase,
        Professional $owner,
        WorkflowSourceVersion $source,
        CaseProtocolStage $stage,
        CaseTaskKind $kind,
        string $title,
        DateTimeImmutable $dueAt,
        Professional $createdBy,
        DateTimeImmutable $createdAt,
    ) {
        if ($dueAt < $createdAt) {
            throw new InvalidArgumentException('A case task cannot be due before it was created.');
        }

        $title = trim($title);
        if ($title === '' || mb_strlen($title) > 160) {
            throw new InvalidArgumentException('A case task title must contain between 1 and 160 characters.');
        }

        $this->id = $id;
        $this->managedCase = $managedCase;
        $this->owner = $owner;
        $this->source = $source;
        $this->stage = $stage;
        $this->kind = $kind;
        $this->title = $title;
        $this->dueAt = $dueAt;
        $this->status = CaseTaskStatus::Pending;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
    }

    public function complete(Professional $actor, DateTimeImmutable $at): void
    {
        $this->resolve(CaseTaskStatus::Completed, $actor, $at);
    }

    public function markNotApplicable(Professional $actor, DateTimeImmutable $at, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 500) {
            throw new InvalidArgumentException('A not-applicable reason must contain between 1 and 500 characters.');
        }

        $this->resolve(CaseTaskStatus::NotApplicable, $actor, $at);
        $this->notApplicableReason = $reason;
    }

    public function isOverdue(DateTimeImmutable $at): bool
    {
        return $this->status === CaseTaskStatus::Pending && $at > $this->dueAt;
    }

    private function resolve(CaseTaskStatus $status, Professional $actor, DateTimeImmutable $at): void
    {
        if ($this->status !== CaseTaskStatus::Pending) {
            throw new LogicException('A resolved case task cannot be changed.');
        }
        if ($at < $this->createdAt) {
            throw new LogicException('A case task cannot be resolved before it was created.');
        }

        $this->status = $status;
        $this->resolvedBy = $actor;
        $this->resolvedAt = $at;
    }

    public function id(): Uuid { return $this->id; }
    public function managedCase(): ManagedCase { return $this->managedCase; }
    public function owner(): Professional { return $this->owner; }
    public function source(): WorkflowSourceVersion { return $this->source; }
    public function stage(): CaseProtocolStage { return $this->stage; }
    public function kind(): CaseTaskKind { return $this->kind; }
    public function title(): string { return $this->title; }
    public function dueAt(): DateTimeImmutable { return $this->dueAt; }
    public function status(): CaseTaskStatus { return $this->status; }
    public function createdBy(): Professional { return $this->createdBy; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function resolvedBy(): ?Professional { return $this->resolvedBy; }
    public function resolvedAt(): ?DateTimeImmutable { return $this->resolvedAt; }
    public function notApplicableReason(): ?string { return $this->notApplicableReason; }
}
