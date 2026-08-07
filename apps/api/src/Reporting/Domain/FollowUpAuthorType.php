<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

/**
 * Distinguishes who authored a follow-up entry without identifying
 * them (#25). Only Reporter is produced today; Professional is
 * reserved for #34.
 */
enum FollowUpAuthorType: string
{
    case Reporter = 'reporter';
    case Professional = 'professional';
}
