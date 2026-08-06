<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure;

use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAccessSecret;
use App\Reporting\Domain\ReportRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineReportRepository implements ReportRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function save(Report $report): void
    {
        $this->entityManager->persist($report);
        $this->entityManager->flush();
    }

    public function findByPublicReference(string $publicReference): ?Report
    {
        return $this->entityManager
            ->getRepository(Report::class)
            ->findOneBy(['publicReference' => $publicReference]);
    }

    public function findByAccessSecret(ReportAccessSecret $accessSecret): ?Report
    {
        return $this->entityManager
            ->getRepository(Report::class)
            ->findOneBy(['accessSecretHash' => $accessSecret->lookupHash()]);
    }
}
