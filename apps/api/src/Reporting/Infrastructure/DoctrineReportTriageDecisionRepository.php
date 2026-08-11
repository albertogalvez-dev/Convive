<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure;

use App\Cases\Domain\ManagedCase;
use App\Professionals\Domain\Professional;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportTriageAlreadyFinalised;
use App\Reporting\Domain\ReportTriageDecision;
use App\Reporting\Domain\ReportTriageDecisionRepository;
use App\Reporting\Domain\ReportTriageOutcome;
use App\Reporting\Domain\ReportTriageReason;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineReportTriageDecisionRepository implements ReportTriageDecisionRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function record(
        Report $report,
        Professional $professional,
        ReportTriageOutcome $outcome,
        ReportTriageReason $reason,
        DateTimeImmutable $decidedAt,
    ): ReportTriageDecision {
        return $this->entityManager->wrapInTransaction(function () use (
            $report,
            $professional,
            $outcome,
            $reason,
            $decidedAt,
        ): ReportTriageDecision {
            $this->entityManager->getConnection()->executeQuery(
                'SELECT pg_advisory_xact_lock(hashtextextended(:report_id, 0))',
                ['report_id' => $report->id()->toRfc4122()],
            );

            $terminal = $this->entityManager
                ->getRepository(ReportTriageDecision::class)
                ->findOneBy(['terminalReport' => $report]);

            if ($terminal instanceof ReportTriageDecision) {
                if (
                    $outcome === ReportTriageOutcome::LinkToCase
                    && $terminal->outcome() === ReportTriageOutcome::LinkToCase
                ) {
                    return $terminal;
                }

                throw new ReportTriageAlreadyFinalised();
            }

            $managedCase = $outcome === ReportTriageOutcome::LinkToCase
                ? new ManagedCase(Uuid::v7(), $report->organisation(), $professional, $decidedAt)
                : null;
            $decision = new ReportTriageDecision(
                Uuid::v7(),
                $report,
                $professional,
                $outcome,
                $reason,
                $decidedAt,
                $managedCase,
            );

            if ($managedCase !== null) {
                $this->entityManager->persist($managedCase);
            }

            $this->entityManager->persist($decision);
            $this->entityManager->flush();

            return $decision;
        });
    }

    public function findByReport(Report $report): array
    {
        return $this->entityManager
            ->getRepository(ReportTriageDecision::class)
            ->findBy(
                ['report' => $report],
                ['decidedAt' => 'ASC', 'id' => 'ASC'],
            );
    }
}
