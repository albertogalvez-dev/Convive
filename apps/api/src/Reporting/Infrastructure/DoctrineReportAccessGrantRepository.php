<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure;

use App\Reporting\Domain\ReportAccessCapability;
use App\Reporting\Domain\ReportAccessGrant;
use App\Reporting\Domain\ReportAccessGrantRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineReportAccessGrantRepository implements ReportAccessGrantRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function save(ReportAccessGrant $grant): void
    {
        $this->entityManager->persist($grant);
        $this->entityManager->flush();
    }

    public function findByCapability(ReportAccessCapability $capability): ?ReportAccessGrant
    {
        return $this->entityManager
            ->getRepository(ReportAccessGrant::class)
            ->findOneBy(['capabilityHash' => $capability->lookupHash()]);
    }
}
