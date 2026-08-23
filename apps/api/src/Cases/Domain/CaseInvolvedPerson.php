<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use App\Professionals\Domain\Professional;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'case_involved_people')]
#[ORM\Index(name: 'idx_case_involved_person_added_by', columns: ['added_by_professional_id'])]
#[ORM\Index(name: 'idx_case_involved_people_case', columns: ['case_id'])]
class CaseInvolvedPerson
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: ManagedCase::class)]
    #[ORM\JoinColumn(name: 'case_id', referencedColumnName: 'id', nullable: false)]
    private ManagedCase $managedCase;

    #[ORM\Column(type: Types::STRING, length: CaseInvolvedPersonName::MAX_LENGTH)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: CaseInvolvedPersonRole::class)]
    private CaseInvolvedPersonRole $role;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'added_by_professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $addedBy;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $addedAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $correctedAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $removedAt = null;

    public function __construct(
        Uuid $id,
        ManagedCase $managedCase,
        CaseInvolvedPersonName $name,
        CaseInvolvedPersonRole $role,
        Professional $addedBy,
        DateTimeImmutable $addedAt,
    ) {
        $this->id = $id;
        $this->managedCase = $managedCase;
        $this->name = $name->toString();
        $this->role = $role;
        $this->addedBy = $addedBy;
        $this->addedAt = $addedAt;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function correct(CaseInvolvedPersonName $name, CaseInvolvedPersonRole $role, DateTimeImmutable $now): void
    {
        if ($this->removedAt !== null) {
            throw new \LogicException('A removed involved person cannot be corrected.');
        }
        $this->name = $name->toString();
        $this->role = $role;
        $this->correctedAt = $now;
        $this->managedCase->recordOperationalActivity($now);
    }

    public function removeAt(DateTimeImmutable $now): void
    {
        if ($this->removedAt !== null) throw new \LogicException('An involved person has already been removed.');
        $this->removedAt = $now;
        $this->managedCase->recordOperationalActivity($now);
    }

    public function isActive(): bool
    {
        return $this->removedAt === null;
    }

    public function removedAt(): ?DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function managedCase(): ManagedCase
    {
        return $this->managedCase;
    }

    public function name(): CaseInvolvedPersonName
    {
        return CaseInvolvedPersonName::fromString($this->name);
    }

    public function role(): CaseInvolvedPersonRole
    {
        return $this->role;
    }

    public function addedBy(): Professional
    {
        return $this->addedBy;
    }

    public function addedAt(): DateTimeImmutable
    {
        return $this->addedAt;
    }
}
