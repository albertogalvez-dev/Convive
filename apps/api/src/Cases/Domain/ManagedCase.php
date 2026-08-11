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

    public function __construct(
        Uuid $id,
        Organisation $organisation,
        Professional $createdBy,
        DateTimeImmutable $createdAt,
    ) {
        $this->id = $id;
        $this->organisation = $organisation;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
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
}
