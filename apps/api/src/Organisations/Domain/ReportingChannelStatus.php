<?php

declare(strict_types=1);

namespace App\Organisations\Domain;

/**
 * The state of a centre's public reporting link.
 *
 * Only `Active` accepts a new report. `Paused` and `Retired` are refused
 * exactly like an identifier that never existed, so the state of a real centre
 * is never observable from outside.
 */
enum ReportingChannelStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Retired = 'retired';
}
