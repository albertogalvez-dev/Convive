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
#[ORM\Table(name: 'case_audit_events')]
#[ORM\Index(name: 'idx_case_audit_event_actor', columns: ['actor_professional_id'])]
#[ORM\Index(name: 'idx_case_audit_event_case_occurred', columns: ['case_id', 'occurred_at'])]
#[ORM\Index(name: 'idx_case_audit_event_organisation_occurred', columns: ['organisation_id', 'occurred_at'])]
class CaseAuditEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: ManagedCase::class)]
    #[ORM\JoinColumn(name: 'case_id', referencedColumnName: 'id', nullable: false)]
    private ManagedCase $managedCase;

    #[ORM\ManyToOne(targetEntity: Organisation::class)]
    #[ORM\JoinColumn(name: 'organisation_id', referencedColumnName: 'id', nullable: false)]
    private Organisation $organisation;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'actor_professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $actor;

    #[ORM\Column(type: Types::STRING, length: 40, enumType: CaseAuditAction::class)]
    private CaseAuditAction $action;

    #[ORM\Column(type: Types::STRING, length: 30, enumType: CaseAuditTarget::class)]
    private CaseAuditTarget $target;

    #[ORM\Column(type: 'uuid')]
    private Uuid $targetId;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $occurredAt;

    public function __construct(
        Uuid $id,
        ManagedCase $managedCase,
        Professional $actor,
        CaseAuditAction $action,
        CaseAuditTarget $target,
        Uuid $targetId,
        DateTimeImmutable $occurredAt,
    ) {
        $this->id = $id;
        $this->managedCase = $managedCase;
        $this->organisation = $managedCase->organisation();
        $this->actor = $actor;
        $this->action = $action;
        $this->target = $target;
        $this->targetId = $targetId;
        $this->occurredAt = $occurredAt;
    }

    public function id(): Uuid { return $this->id; }
    public function managedCase(): ManagedCase { return $this->managedCase; }
    public function organisation(): Organisation { return $this->organisation; }
    public function actor(): Professional { return $this->actor; }
    public function action(): CaseAuditAction { return $this->action; }
    public function target(): CaseAuditTarget { return $this->target; }
    public function targetId(): Uuid { return $this->targetId; }
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }
}
