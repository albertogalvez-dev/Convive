<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

/**
 * Distinguishes who authored a follow-up entry without identifying
 * them in reporter-visible representations. Internal notes are a
 * separate concept and must never be persisted as follow-up entries.
 */
enum FollowUpAuthorType: string
{
    case Reporter = 'reporter';
    case Professional = 'professional';
}
