<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use App\Professionals\Domain\Professional;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use LogicException;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'case_assignments')]
#[ORM\Index(name: 'idx_case_assignment_assigned_by', columns: ['assigned_by_professional_id'])]
#[ORM\UniqueConstraint(name: 'uniq_case_assignment_professional', columns: ['case_id', 'professional_id'])]
#[ORM\Index(name: 'idx_case_assignment_professional_active', columns: ['professional_id', 'revoked_at'])]
class CaseAssignment
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: ManagedCase::class)]
    #[ORM\JoinColumn(name: 'case_id', referencedColumnName: 'id', nullable: false)]
    private ManagedCase $managedCase;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $professional;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: CaseAssignmentRole::class)]
    private CaseAssignmentRole $role;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'assigned_by_professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $assignedBy;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $assignedAt;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $assignmentReason;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $roleChangeReason = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $revokedAt = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $revocationReason = null;

    public function __construct(
        Uuid $id,
        ManagedCase $managedCase,
        Professional $professional,
        CaseAssignmentRole $role,
        Professional $assignedBy,
        DateTimeImmutable $assignedAt,
        ?string $assignmentReason = null,
    ) {
        $this->id = $id;
        $this->managedCase = $managedCase;
        $this->professional = $professional;
        $this->role = $role;
        $this->assignedBy = $assignedBy;
        $this->assignedAt = $assignedAt;
        $this->assignmentReason = self::reason($assignmentReason);
        $this->managedCase->recordOperationalActivity($assignedAt);
    }

    public function permits(CasePermission $permission): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        return match ($this->role) {
            CaseAssignmentRole::Lead => true,
            CaseAssignmentRole::Contributor => $permission !== CasePermission::ManageAssignments
                && $permission !== CasePermission::Export
                && $permission !== CasePermission::ViewAudit,
            CaseAssignmentRole::Observer => $permission === CasePermission::View,
        };
    }

    public function revokeAt(DateTimeImmutable $now, string $reason): void
    {
        if ($now < $this->assignedAt) {
            throw new LogicException('A case assignment cannot be revoked before it was assigned.');
        }

        if ($this->revokedAt !== null) {
            throw new LogicException('A case assignment has already been revoked.');
        }

        $this->revokedAt = $now;
        $this->revocationReason = self::requiredReason($reason);
        $this->managedCase->recordOperationalActivity($now);
    }

    public function changeRole(CaseAssignmentRole $role, DateTimeImmutable $now, string $reason): void
    {
        if (!$this->isActive()) {
            throw new LogicException('A revoked case assignment cannot be changed.');
        }
        if ($this->role === CaseAssignmentRole::Lead || $role === CaseAssignmentRole::Lead) {
            throw new LogicException('Lead responsibility can only change through an explicit handover.');
        }
        if ($this->role === $role) {
            throw new LogicException('The case assignment already has this role.');
        }

        $this->role = $role;
        $this->roleChangeReason = self::requiredReason($reason);
        $this->managedCase->recordOperationalActivity($now);
    }

    public function isActive(): bool
    {
        return $this->revokedAt === null;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function managedCase(): ManagedCase
    {
        return $this->managedCase;
    }

    public function professional(): Professional
    {
        return $this->professional;
    }

    public function role(): CaseAssignmentRole
    {
        return $this->role;
    }

    public function assignedBy(): Professional
    {
        return $this->assignedBy;
    }

    public function assignedAt(): DateTimeImmutable
    {
        return $this->assignedAt;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function assignmentReason(): ?string
    {
        return $this->assignmentReason;
    }

    public function revocationReason(): ?string
    {
        return $this->revocationReason;
    }

    public function roleChangeReason(): ?string
    {
        return $this->roleChangeReason;
    }

    private static function reason(?string $reason): ?string
    {
        if ($reason === null) {
            return null;
        }

        return self::requiredReason($reason);
    }

    private static function requiredReason(string $reason): string
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 500) {
            throw new \InvalidArgumentException('An assignment reason must contain between 1 and 500 characters.');
        }

        return $reason;
    }
}
