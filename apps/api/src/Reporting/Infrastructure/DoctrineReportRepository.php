<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure;

use App\Organisations\Domain\Organisation;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAccessSecret;
use App\Reporting\Domain\ReportListCursor;
use App\Reporting\Domain\ReportPage;
use App\Reporting\Domain\ReportRepository;
use App\Reporting\Domain\ReportStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

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

    public function findPageForOrganisations(
        array $organisations,
        ?ReportStatus $status,
        ?ReportListCursor $cursor,
        int $limit,
    ): ReportPage {
        $query = $this->entityManager
            ->createQueryBuilder()
            ->select('report')
            ->from(Report::class, 'report')
            ->where('report.organisation IN (:organisations)')
            ->setParameter('organisations', $organisations)
            ->orderBy('report.createdAt', 'DESC')
            ->addOrderBy('report.id', 'DESC')
            ->setMaxResults($limit + 1);

        if ($status !== null) {
            $query
                ->andWhere('report.status = :status')
                ->setParameter('status', $status->value);
        }

        if ($cursor !== null) {
            $query
                ->andWhere(
                    'report.createdAt < :cursorCreatedAt OR '
                    .'(report.createdAt = :cursorCreatedAt AND report.id < :cursorId)',
                )
                ->setParameter('cursorCreatedAt', $cursor->createdAt)
                ->setParameter('cursorId', $cursor->id);
        }

        /** @var list<Report> $reports */
        $reports = $query->getQuery()->getResult();
        $hasNextPage = count($reports) > $limit;

        if ($hasNextPage) {
            array_pop($reports);
        }

        $last = $reports === [] ? null : $reports[array_key_last($reports)];

        return new ReportPage(
            $reports,
            $hasNextPage && $last !== null
                ? new ReportListCursor(
                    $last->createdAt()->setTime(
                        (int) $last->createdAt()->format('H'),
                        (int) $last->createdAt()->format('i'),
                        (int) $last->createdAt()->format('s'),
                    ),
                    $last->id(),
                )
                : null,
        );
    }

    public function findByIdForOrganisations(Uuid $id, array $organisations): ?Report
    {
        /** @var Report|null */
        return $this->entityManager
            ->createQueryBuilder()
            ->select('report')
            ->from(Report::class, 'report')
            ->where('report.id = :id')
            ->andWhere('report.organisation IN (:organisations)')
            ->setParameter('id', $id)
            ->setParameter('organisations', $organisations)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
