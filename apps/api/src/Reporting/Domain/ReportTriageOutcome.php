<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

enum ReportTriageOutcome: string
{
    case Keep = 'keep';
    case Redirect = 'redirect';
    case Dismiss = 'dismiss';
    case LinkToCase = 'link_to_case';

    public function isTerminal(): bool
    {
        return $this !== self::Keep;
    }
}
