<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Domain;

use App\Cases\Domain\CaseModality;
use App\Cases\Domain\CaseStatus;
use App\Cases\Domain\ManagedCase;
use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportReviewReason;
use App\Reporting\Domain\ReportTriageDecision;
use App\Reporting\Domain\ReportTriageOutcome;
use App\Reporting\Domain\ReportTriageReason;
use App\Reporting\Domain\ReporterProgress;
use App\Reporting\Domain\ReporterProgressStage;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ReporterProgressTest extends TestCase
{
    public function testAnUnopenedReportReadsAsReceived(): void
    {
        $report = self::report();

        self::assertSame(
            ReporterProgressStage::Received,
            ReporterProgress::stageFor($report, []),
        );
    }

    public function testAReadButUndecidedReportReadsAsUnderReview(): void
    {
        $report = self::report();
        $report->review(
            ReportReviewReason::fromString(
                'Fictional review of the submitted situation.',
            ),
            self::professional()->id(),
            new DateTimeImmutable('2030-01-02T10:00:00+00:00'),
        );

        self::assertSame(
            ReporterProgressStage::UnderReview,
            ReporterProgress::stageFor($report, []),
        );
    }

    public function testAKeptReportReadsAsUnderReviewBecauseNoWorkHasStarted(): void
    {
        $report = self::report();
        $decision = self::decision($report, ReportTriageOutcome::Keep);

        self::assertSame(
            ReporterProgressStage::UnderReview,
            ReporterProgress::stageFor($report, [$decision]),
        );
    }

    public function testAReportLinkedToAnOpenCaseReadsAsActionTaken(): void
    {
        $report = self::report();
        $case = self::managedCase($report->organisation());
        $decision = self::decision(
            $report,
            ReportTriageOutcome::LinkToCase,
            $case,
        );

        self::assertSame(
            ReporterProgressStage::ActionTaken,
            ReporterProgress::stageFor($report, [$decision]),
        );
    }

    public function testAReportLinkedToAClosedCaseReadsAsClosed(): void
    {
        $report = self::report();
        $case = self::managedCase($report->organisation());
        $case->transitionTo(
            CaseStatus::Closed,
            'Fictional closure reason.',
            'Fictional closure evidence.',
            new DateTimeImmutable('2030-02-01T10:00:00+00:00'),
        );
        $decision = self::decision(
            $report,
            ReportTriageOutcome::LinkToCase,
            $case,
        );

        self::assertSame(
            ReporterProgressStage::Closed,
            ReporterProgress::stageFor($report, [$decision]),
        );
    }

    /**
     * The heart of this surface's privacy guarantee: a report the
     * organisation sent elsewhere and one it decided not to pursue are
     * different internal decisions, and the reporter is shown the same word
     * for both. If these ever diverge, the stage discloses the decision.
     */
    public function testRedirectAndDismissAreIndistinguishableToTheReporter(): void
    {
        $redirected = self::report();
        $dismissed = self::report();

        $redirectStage = ReporterProgress::stageFor(
            $redirected,
            [self::decision($redirected, ReportTriageOutcome::Redirect)],
        );
        $dismissStage = ReporterProgress::stageFor(
            $dismissed,
            [self::decision($dismissed, ReportTriageOutcome::Dismiss)],
        );

        self::assertSame($redirectStage, $dismissStage);
        self::assertSame(ReporterProgressStage::Closed, $redirectStage);
    }

    public function testTheMostRecentDecisionWins(): void
    {
        $report = self::report();
        $case = self::managedCase($report->organisation());

        $earlier = self::decision(
            $report,
            ReportTriageOutcome::Keep,
            null,
            new DateTimeImmutable('2030-01-02T10:00:00+00:00'),
        );
        $later = self::decision(
            $report,
            ReportTriageOutcome::LinkToCase,
            $case,
            new DateTimeImmutable('2030-01-09T10:00:00+00:00'),
        );

        // Order of the array must not matter; only decidedAt does.
        self::assertSame(
            ReporterProgressStage::ActionTaken,
            ReporterProgress::stageFor($report, [$later, $earlier]),
        );
        self::assertSame(
            ReporterProgressStage::ActionTaken,
            ReporterProgress::stageFor($report, [$earlier, $later]),
        );
    }

    /**
     * Every stage the reporter can be shown must be one of the four agreed
     * words. A new internal outcome must not be able to leak a fifth.
     */
    public function testEveryTriageOutcomeMapsOntoTheFixedReporterVocabulary(): void
    {
        $permitted = [
            ReporterProgressStage::Received,
            ReporterProgressStage::UnderReview,
            ReporterProgressStage::ActionTaken,
            ReporterProgressStage::Closed,
        ];

        foreach (ReportTriageOutcome::cases() as $outcome) {
            $report = self::report();
            $case = $outcome === ReportTriageOutcome::LinkToCase
                ? self::managedCase($report->organisation())
                : null;

            $stage = ReporterProgress::stageFor(
                $report,
                [self::decision($report, $outcome, $case)],
            );

            self::assertContains(
                $stage,
                $permitted,
                sprintf(
                    'Triage outcome "%s" produced a stage outside the reporter vocabulary.',
                    $outcome->value,
                ),
            );
        }
    }

    private static function report(): Report
    {
        return Report::create(
            self::organisation(),
            SituationDescription::fromString(
                'A fictional student is being excluded repeatedly during break time.',
            ),
            SituationContext::InPerson,
        )->report;
    }

    private static function organisation(): Organisation
    {
        return new Organisation(
            Uuid::v7(),
            'IES Valle Sereno',
            PublicReportingIdentifier::fromString('ORG_7M4K9T2W6N8Q3R5X'),
        );
    }

    private static function professional(): Professional
    {
        return new Professional(
            Uuid::v7(),
            'Fictional Professional',
            ProfessionalEmail::fromString('fictional@example.test'),
            new DateTimeImmutable('2030-01-01T09:00:00+00:00'),
        );
    }

    private static function managedCase(Organisation $organisation): ManagedCase
    {
        return new ManagedCase(
            Uuid::v7(),
            $organisation,
            self::professional(),
            new DateTimeImmutable('2030-01-01T10:00:00+00:00'),
            CaseModality::Mixed,
        );
    }

    private static function decision(
        Report $report,
        ReportTriageOutcome $outcome,
        ?ManagedCase $managedCase = null,
        ?DateTimeImmutable $decidedAt = null,
    ): ReportTriageDecision {
        return new ReportTriageDecision(
            Uuid::v7(),
            $report,
            self::professional(),
            $outcome,
            ReportTriageReason::fromString(
                'Fictional triage reasoning that the reporter must never see.',
            ),
            $decidedAt ?? new DateTimeImmutable('2030-01-05T10:00:00+00:00'),
            $managedCase,
        );
    }
}
