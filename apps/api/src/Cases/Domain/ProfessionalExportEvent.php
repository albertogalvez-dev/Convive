<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use App\Professionals\Domain\Professional;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'professional_export_events')]
#[ORM\Index(name: 'idx_professional_export_event_actor_occurred', columns: ['professional_id', 'occurred_at'])]
final class ProfessionalExportEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $professional;

    #[ORM\Column(type: Types::STRING, length: 40, enumType: ProfessionalExportKind::class)]
    private ProfessionalExportKind $kind;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $occurredAt;

    public function __construct(
        Uuid $id,
        Professional $professional,
        ProfessionalExportKind $kind,
        DateTimeImmutable $occurredAt,
    ) {
        $this->id = $id;
        $this->professional = $professional;
        $this->kind = $kind;
        $this->occurredAt = $occurredAt;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function professional(): Professional
    {
        return $this->professional;
    }

    public function kind(): ProfessionalExportKind
    {
        return $this->kind;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
