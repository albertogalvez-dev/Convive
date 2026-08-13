<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

enum ProfessionalConcernCategory: string
{
    case PeerInteraction = 'peer_interaction';
    case DigitalInteraction = 'digital_interaction';
    case ExclusionOrIsolation = 'exclusion_or_isolation';
    case HarmfulLanguageOrConduct = 'harmful_language_or_conduct';
    case SafetyOrWellbeingConcern = 'safety_or_wellbeing_concern';
    case Other = 'other';
    case Unknown = 'unknown';
}
