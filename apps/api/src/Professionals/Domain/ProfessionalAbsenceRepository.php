<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

interface ProfessionalAbsenceRepository
{
    public function save(ProfessionalAbsence $absence): void;

    /** Resolves an absence only when the professional recorded it about themselves. */
    public function findOwn(Uuid $id, Professional $professional): ?ProfessionalAbsence;

    /**
     * Absences recorded by this professional that have not been cancelled,
     * most recent period first.
     *
     * @return list<ProfessionalAbsence>
     */
    public function findActiveFor(Professional $professional): array;

    /**
     * The professionals from the given set who are absent on the given day.
     *
     * @param list<Professional> $professionals
     *
     * @return list<string> RFC 4122 identifiers of the absent professionals
     */
    public function findAbsentIdentifiers(array $professionals, DateTimeImmutable $day): array;
}
