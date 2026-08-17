<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use App\Cases\Domain\CaseStatus;

/**
 * Derives the one stage a reporter is shown from real recorded state.
 *
 * The mapping is deliberately lossy in one direction only: several distinct
 * internal situations collapse into the same reporter-facing stage, and no
 * reporter-facing stage can be read back to recover which one it was. That is
 * the point -- see {@see ReporterProgressStage}.
 */
final readonly class ReporterProgress
{
    /**
     * @param list<ReportTriageDecision> $triageDecisions every decision
     *                                                    recorded against the
     *                                                    report, in any order
     */
    public static function stageFor(Report $report, array $triageDecisions): ReporterProgressStage
    {
        $decision = self::latestDecision($triageDecisions);

        if ($decision === null) {
            // Nothing decided yet: the only thing that has happened is that a
            // professional either has or has not opened it.
            return $report->status() === ReportStatus::Reviewed
                ? ReporterProgressStage::UnderReview
                : ReporterProgressStage::Received;
        }

        if ($decision->outcome() === ReportTriageOutcome::LinkToCase) {
            $case = $decision->managedCase();

            // A linked case is the only situation where the organisation is
            // demonstrably still working. Once that case closes, so does the
            // reporter's view of it.
            return $case !== null && $case->status() === CaseStatus::Closed
                ? ReporterProgressStage::Closed
                : ReporterProgressStage::ActionTaken;
        }

        if ($decision->outcome()->isTerminal()) {
            // Redirect and Dismiss are different decisions internally and
            // both end the report here. They intentionally read the same.
            return ReporterProgressStage::Closed;
        }

        // Keep: reviewed and retained, but no case yet.
        return ReporterProgressStage::UnderReview;
    }

    /**
     * @param list<ReportTriageDecision> $triageDecisions
     */
    private static function latestDecision(array $triageDecisions): ?ReportTriageDecision
    {
        $latest = null;

        foreach ($triageDecisions as $decision) {
            if ($latest === null || $decision->decidedAt() >= $latest->decidedAt()) {
                $latest = $decision;
            }
        }

        return $latest;
    }
}
