<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

use App\Cases\Domain\ManagedCase;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'professional_notifications')]
#[ORM\Index(name: 'idx_professional_notification_recipient_created', columns: ['recipient_professional_id', 'created_at', 'id'])]
class ProfessionalNotification
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'recipient_professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $recipient;

    #[ORM\ManyToOne(targetEntity: ManagedCase::class)]
    #[ORM\JoinColumn(name: 'case_id', referencedColumnName: 'id', nullable: false)]
    private ManagedCase $managedCase;

    #[ORM\Column(type: Types::STRING, length: 30, enumType: ProfessionalNotificationType::class)]
    private ProfessionalNotificationType $type;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $readAt = null;

    public function __construct(Uuid $id, Professional $recipient, ManagedCase $managedCase, ProfessionalNotificationType $type, DateTimeImmutable $createdAt)
    {
        $this->id = $id;
        $this->recipient = $recipient;
        $this->managedCase = $managedCase;
        $this->type = $type;
        $this->createdAt = $createdAt;
    }

    public function id(): Uuid { return $this->id; }
    public function recipient(): Professional { return $this->recipient; }
    public function managedCase(): ManagedCase { return $this->managedCase; }
    public function type(): ProfessionalNotificationType { return $this->type; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function readAt(): ?DateTimeImmutable { return $this->readAt; }
    public function markRead(DateTimeImmutable $at): void { $this->readAt ??= $at; }
}
