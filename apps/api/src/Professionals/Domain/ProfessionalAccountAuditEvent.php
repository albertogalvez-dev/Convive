<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'professional_account_audit_events')]
#[ORM\Index(name: 'idx_professional_account_audit_target_occurred', columns: ['target_professional_id', 'occurred_at'])]
class ProfessionalAccountAuditEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'target_professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $target;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'actor_professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $actor;

    #[ORM\Column(type: Types::STRING, length: 32, enumType: ProfessionalAccountAuditAction::class)]
    private ProfessionalAccountAuditAction $action;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $occurredAt;

    public function __construct(Uuid $id, Professional $target, Professional $actor, ProfessionalAccountAuditAction $action, DateTimeImmutable $occurredAt)
    {
        $this->id = $id;
        $this->target = $target;
        $this->actor = $actor;
        $this->action = $action;
        $this->occurredAt = $occurredAt;
    }

    /**
     * Read access to an append-only record.
     *
     * The events carry no report or case content, only who acted on which
     * account and how, so exposing them is safe. Without accessors the record
     * cannot be verified at all, and an audit trail nobody can inspect is not
     * an audit trail.
     */
    public function target(): Professional
    {
        return $this->target;
    }

    public function actor(): Professional
    {
        return $this->actor;
    }

    public function action(): ProfessionalAccountAuditAction
    {
        return $this->action;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
