<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * A planned absence recorded by a professional about themselves. It is an
 * operational signal only: it never changes who can reach a case, and it never
 * transfers a case on its own. Continuity is restored by an explicit
 * reassignment, which is a separate accountable act.
 */
#[ORM\Entity]
#[ORM\Table(name: 'professional_absences')]
#[ORM\Index(name: 'idx_professional_absence_professional_period', columns: ['professional_id', 'starts_on', 'ends_on'])]
class ProfessionalAbsence
{
    public const MAX_REASON_LENGTH = 200;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $professional;

    #[ORM\Column(name: 'starts_on', type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $startsOn;

    #[ORM\Column(name: 'ends_on', type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $endsOn;

    #[ORM\Column(type: Types::STRING, length: self::MAX_REASON_LENGTH, nullable: true)]
    private ?string $note;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $recordedAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $cancelledAt = null;

    public function __construct(
        Uuid $id,
        Professional $professional,
        DateTimeImmutable $startsOn,
        DateTimeImmutable $endsOn,
        ?string $note,
        DateTimeImmutable $recordedAt,
    ) {
        if ($endsOn < $startsOn) {
            throw new InvalidArgumentException('An absence must end on or after the day it starts.');
        }

        $note = $note === null ? null : trim($note);
        if ($note === '') {
            $note = null;
        }
        if ($note !== null && mb_strlen($note) > self::MAX_REASON_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('An absence note must not exceed %d characters.', self::MAX_REASON_LENGTH),
            );
        }

        $this->id = $id;
        $this->professional = $professional;
        $this->startsOn = $startsOn->setTime(0, 0);
        $this->endsOn = $endsOn->setTime(0, 0);
        $this->note = $note;
        $this->recordedAt = $recordedAt;
    }

    public function id(): Uuid { return $this->id; }
    public function professional(): Professional { return $this->professional; }
    public function startsOn(): DateTimeImmutable { return $this->startsOn; }
    public function endsOn(): DateTimeImmutable { return $this->endsOn; }
    public function note(): ?string { return $this->note; }
    public function recordedAt(): DateTimeImmutable { return $this->recordedAt; }
    public function cancelledAt(): ?DateTimeImmutable { return $this->cancelledAt; }

    public function cancel(DateTimeImmutable $at): void
    {
        $this->cancelledAt ??= $at;
    }

    public function coversDay(DateTimeImmutable $day): bool
    {
        if ($this->cancelledAt !== null) {
            return false;
        }

        $day = $day->setTime(0, 0);

        return $day >= $this->startsOn && $day <= $this->endsOn;
    }
}
