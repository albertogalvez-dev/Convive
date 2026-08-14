<?php

declare(strict_types=1);

namespace App\Cases\Domain;

enum CaseCommunicationStatus: string
{
    case Planned = 'planned';
    case Recorded = 'recorded';
    case NotApplicable = 'not_applicable';
}
