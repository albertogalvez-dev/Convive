<?php

declare(strict_types=1);

namespace App\Cases\Domain;

enum CaseTaskKind: string
{
    case InternalAction = 'internal_action';
    case ExternalCommunication = 'external_communication';
}
