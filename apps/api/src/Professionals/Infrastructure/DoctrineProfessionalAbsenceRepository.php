<?php

declare(strict_types=1);

namespace App\Professionals\Infrastructure;

use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalAbsence;
use App\Professionals\Domain\ProfessionalAbsenceRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineProfessionalAbsenceRepository implements ProfessionalAbsenceRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(ProfessionalAbsence $absence): void
    {
        $this->entityManager->persist($absence);
        $this->entityManager->flush();
    }

    public function findOwn(Uuid $id, Professional $professional): ?ProfessionalAbsence
    {
        return $this->entityManager
            ->getRepository(ProfessionalAbsence::class)
            ->findOneBy(['id' => $id, 'professional' => $professional]);
    }

    public function findActiveFor(Professional $professional): array
    {
        /** @var list<ProfessionalAbsence> */
        return $this->entityManager->createQueryBuilder()
            ->select('absence')
            ->from(ProfessionalAbsence::class, 'absence')
            ->where('absence.professional = :professional')
            ->andWhere('absence.cancelledAt IS NULL')
            ->setParameter('professional', $professional)
            ->orderBy('absence.startsOn', 'DESC')
            ->addOrderBy('absence.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAbsentIdentifiers(array $professionals, DateTimeImmutable $day): array
    {
        if ($professionals === []) {
            return [];
        }

        /** @var list<array{id: string}> $rows */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT professional.id AS id')
            ->from(ProfessionalAbsence::class, 'absence')
            ->join('absence.professional', 'professional')
            ->where('absence.professional IN (:professionals)')
            ->andWhere('absence.cancelledAt IS NULL')
            ->andWhere('absence.startsOn <= :day')
            ->andWhere('absence.endsOn >= :day')
            ->setParameter('professionals', $professionals)
            ->setParameter('day', $day->setTime(0, 0))
            ->getQuery()
            ->getResult();

        return array_map(static fn (array $row): string => (string) $row['id'], $rows);
    }
}
