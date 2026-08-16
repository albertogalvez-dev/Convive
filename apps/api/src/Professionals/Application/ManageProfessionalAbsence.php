<?php

declare(strict_types=1);

namespace App\Professionals\Application;

use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalAbsence;
use App\Professionals\Domain\ProfessionalAbsenceRepository;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * A professional records planned absence about themselves. Recording it never
 * moves a case, never widens anyone's access and never removes the recorder's
 * own access: it only makes the gap visible so an accountable reassignment can
 * be decided.
 */
final readonly class ManageProfessionalAbsence
{
    public function __construct(private ProfessionalAbsenceRepository $absences)
    {
    }

    public function record(
        Professional $professional,
        DateTimeImmutable $startsOn,
        DateTimeImmutable $endsOn,
        ?string $note,
        DateTimeImmutable $now,
    ): ProfessionalAbsence {
        $absence = new ProfessionalAbsence(Uuid::v7(), $professional, $startsOn, $endsOn, $note, $now);
        $this->absences->save($absence);

        return $absence;
    }

    public function cancel(ProfessionalAbsence $absence, DateTimeImmutable $now): void
    {
        $absence->cancel($now);
        $this->absences->save($absence);
    }
}
