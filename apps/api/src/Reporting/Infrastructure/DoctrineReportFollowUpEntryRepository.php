<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure;

use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportFollowUpEntry;
use App\Reporting\Domain\ReportFollowUpEntryRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineReportFollowUpEntryRepository implements ReportFollowUpEntryRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function save(ReportFollowUpEntry $entry): void
    {
        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }

    public function findByReportOrderedByCreatedAt(Report $report): array
    {
        return $this->entityManager
            ->getRepository(ReportFollowUpEntry::class)
            ->findBy(['report' => $report], ['createdAt' => 'ASC']);
    }
}
