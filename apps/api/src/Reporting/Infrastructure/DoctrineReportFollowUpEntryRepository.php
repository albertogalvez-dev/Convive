<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure;

use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportFollowUpEntry;
use App\Reporting\Domain\ReportFollowUpEntryRepository;
use App\Reporting\Domain\FollowUpAuthorType;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineReportFollowUpEntryRepository implements ReportFollowUpEntryRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DoctrineReporterEmailNotifications $reporterEmailNotifications,
    ) {
    }

    public function saveIfReportHasCapacity(
        ReportFollowUpEntry $entry,
        int $maximumEntries,
    ): bool
    {
        return $this->entityManager->wrapInTransaction(function () use (
            $entry,
            $maximumEntries,
        ): bool {
            $report = $entry->report();
            $this->entityManager->lock($report, LockMode::PESSIMISTIC_WRITE);

            $entryCount = $this->entityManager
                ->getRepository(ReportFollowUpEntry::class)
                ->count(['report' => $report]);

            if ($entryCount >= $maximumEntries) {
                return false;
            }

            $this->entityManager->persist($entry);

            if ($entry->authorType() === FollowUpAuthorType::Professional) {
                $this->reporterEmailNotifications->queueReportUpdate($report, $entry->id());
            }

            return true;
        });
    }

    public function findByReportOrderedByCreatedAt(
        Report $report,
        int $maximumEntries,
    ): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('entry')
            ->from(ReportFollowUpEntry::class, 'entry')
            ->where('entry.report = :report')
            ->setParameter('report', $report)
            ->orderBy('entry.createdAt', 'ASC')
            ->addOrderBy('entry.id', 'ASC')
            ->setMaxResults($maximumEntries)
            ->getQuery()
            ->getResult();
    }
}
