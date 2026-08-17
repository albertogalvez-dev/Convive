<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

/**
 * What a reporter is told about where their own report stands.
 *
 * Deliberately separate from {@see ReportStatus}, {@see ReportTriageOutcome}
 * and the case-management states a professional works in. Those record what
 * the organisation decided; this records only that the report is moving, and
 * is the single vocabulary the reporter-facing surface is allowed to use.
 *
 * Two rules hold for every value here:
 *
 * - It confirms activity and discloses nothing. No case content, no
 *   professional identity, no triage reason, and no indication of which
 *   internal outcome was chosen -- a report that was redirected elsewhere and
 *   one that was dismissed both read as Closed, because telling the reporter
 *   which one happened would disclose a decision.
 * - It promises nothing. There is no "in progress, expect a reply by...".
 *   When nothing has happened yet the honest answer is Received, not a
 *   fabricated sense of momentum.
 */
enum ReporterProgressStage: string
{
    /** Submitted and stored. No professional has opened it yet. */
    case Received = 'received';

    /** A professional has read it; no decision has been recorded yet. */
    case UnderReview = 'under_review';

    /** It led to work the organisation is currently carrying out. */
    case ActionTaken = 'action_taken';

    /** The organisation has finished with it, however it finished. */
    case Closed = 'closed';
}
