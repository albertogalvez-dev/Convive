<?php

declare(strict_types=1);

namespace App\Reporting\Application\VerifyReportAccess;

use RuntimeException;

/**
 * Deliberately uninformative: malformed and unknown access secrets must be
 * indistinguishable to a caller, so this exception carries no detail about
 * which condition occurred.
 */
final class ReportAccessDenied extends RuntimeException
{
    public static function becauseTheSecretWasRejected(): self
    {
        return new self('The report access secret was not accepted.');
    }
}
