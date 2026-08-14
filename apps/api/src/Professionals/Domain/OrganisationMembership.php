<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

use App\Organisations\Domain\Organisation;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Grants a professional exactly one role within one organisation.
 * A professional needing several roles in the same organisation holds
 * several memberships. Authorisation must check active membership state,
 * never a role cached anywhere else (ADR-0008).
 */
/**
 * One row per (professional, organisation, role) forever — revoking
 * clears the grant rather than deleting the row, and there is no
 * regrant operation yet (out of scope for #29). This is a deliberate
 * simplification: it keeps the uniqueness rule a single ordinary
 * database constraint instead of a partial index Doctrine's schema
 * comparator cannot represent.
 */
#[ORM\Entity]
#[ORM\Table(name: 'organisation_memberships')]
#[ORM\UniqueConstraint(
    name: 'uniq_organisation_memberships_grant',
    columns: ['professional_id', 'organisation_id', 'role'],
)]
class OrganisationMembership
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(
        name: 'professional_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private Professional $professional;

    #[ORM\ManyToOne(targetEntity: Organisation::class)]
    #[ORM\JoinColumn(
        name: 'organisation_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private Organisation $organisation;

    #[ORM\Column(
        type: Types::STRING,
        length: 20,
        enumType: ProfessionalRole::class,
    )]
    private ProfessionalRole $role;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $grantedAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $revokedAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $suspendedAt = null;

    public function __construct(
        Uuid $id,
        Professional $professional,
        Organisation $organisation,
        ProfessionalRole $role,
        DateTimeImmutable $grantedAt,
    ) {
        $this->id = $id;
        $this->professional = $professional;
        $this->organisation = $organisation;
        $this->role = $role;
        $this->grantedAt = $grantedAt;
    }

    public function isActive(): bool
    {
        return $this->revokedAt === null && $this->suspendedAt === null;
    }

    public function revokeAt(DateTimeImmutable $now): void
    {
        if ($this->revokedAt !== null) {
            throw new \LogicException('An organisation membership has already been removed.');
        }
        $this->revokedAt = $now;
    }

    public function suspendAt(DateTimeImmutable $now): void
    {
        if ($this->revokedAt !== null || $this->suspendedAt !== null) {
            throw new \LogicException('Only an active organisation membership can be suspended.');
        }
        $this->suspendedAt = $now;
    }

    public function resume(): void
    {
        if ($this->revokedAt !== null || $this->suspendedAt === null) {
            throw new \LogicException('Only a suspended organisation membership can be resumed.');
        }
        $this->suspendedAt = null;
    }

    public function changeRole(ProfessionalRole $role): void
    {
        if (!$this->isActive()) {
            throw new \LogicException('Only an active organisation membership can change role.');
        }
        if ($this->role === $role) {
            throw new \LogicException('The organisation membership already has this role.');
        }
        $this->role = $role;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function professional(): Professional
    {
        return $this->professional;
    }

    public function organisation(): Organisation
    {
        return $this->organisation;
    }

    public function role(): ProfessionalRole
    {
        return $this->role;
    }

    public function grantedAt(): DateTimeImmutable
    {
        return $this->grantedAt;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function suspendedAt(): ?DateTimeImmutable
    {
        return $this->suspendedAt;
    }
}
