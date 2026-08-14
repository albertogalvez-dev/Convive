<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use App\Professionals\Domain\Professional;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'case_communications')]
#[ORM\Index(name: 'idx_case_communication_case_occurred', columns: ['case_id', 'occurred_at', 'id'])]
class CaseCommunication
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: ManagedCase::class)]
    #[ORM\JoinColumn(name: 'case_id', referencedColumnName: 'id', nullable: false)]
    private ManagedCase $managedCase;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'responsible_professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $responsible;

    #[ORM\Column(type: Types::STRING, length: 30, enumType: CaseCommunicationRecipient::class)]
    private CaseCommunicationRecipient $recipient;

    #[ORM\Column(type: Types::STRING, length: 30, enumType: CaseCommunicationChannel::class)]
    private CaseCommunicationChannel $channel;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: CaseCommunicationStatus::class)]
    private CaseCommunicationStatus $status;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $occurredAt;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private string $note;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'created_by_professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $createdBy;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'supersedes_communication_id', referencedColumnName: 'id', nullable: true)]
    private ?self $supersedes;

    public function __construct(
        Uuid $id,
        ManagedCase $managedCase,
        Professional $responsible,
        CaseCommunicationRecipient $recipient,
        CaseCommunicationChannel $channel,
        CaseCommunicationStatus $status,
        DateTimeImmutable $occurredAt,
        string $note,
        Professional $createdBy,
        DateTimeImmutable $createdAt,
        ?self $supersedes = null,
    ) {
        $note = trim($note);
        if ($note === '' || mb_strlen($note) > 500) {
            throw new InvalidArgumentException('A communication note must contain between 1 and 500 characters.');
        }
        if ($supersedes !== null && !$supersedes->managedCase()->id()->equals($managedCase->id())) {
            throw new InvalidArgumentException('A communication can only supersede a record in the same case.');
        }

        $this->id = $id;
        $this->managedCase = $managedCase;
        $this->responsible = $responsible;
        $this->recipient = $recipient;
        $this->channel = $channel;
        $this->status = $status;
        $this->occurredAt = $occurredAt;
        $this->note = $note;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
        $this->supersedes = $supersedes;
        $this->managedCase->recordOperationalActivity($createdAt);
    }

    public function id(): Uuid { return $this->id; }
    public function managedCase(): ManagedCase { return $this->managedCase; }
    public function responsible(): Professional { return $this->responsible; }
    public function recipient(): CaseCommunicationRecipient { return $this->recipient; }
    public function channel(): CaseCommunicationChannel { return $this->channel; }
    public function status(): CaseCommunicationStatus { return $this->status; }
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }
    public function note(): string { return $this->note; }
    public function createdBy(): Professional { return $this->createdBy; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function supersedes(): ?self { return $this->supersedes; }
}
