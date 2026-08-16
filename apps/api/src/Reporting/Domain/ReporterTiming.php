<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

/**
 * Roughly when the situation happened or started, as the reporter understands
 * it. Deliberately coarse: a person describing something distressing should not
 * have to produce a date, and triage needs recency rather than precision.
 *
 * `Unknown` is a real answer, not a missing one.
 */
enum ReporterTiming: string
{
    case WithinDays = 'within_days';
    case WithinWeeks = 'within_weeks';
    case LongerAgo = 'longer_ago';
    case Unknown = 'unknown';
}
