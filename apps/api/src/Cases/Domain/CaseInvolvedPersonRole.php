<?php

declare(strict_types=1);

namespace App\Cases\Domain;

enum CaseInvolvedPersonRole: string
{
    case Affected = 'affected';
    case AllegedActor = 'alleged_actor';
    case Witness = 'witness';
    case Guardian = 'guardian';
    case Other = 'other';
}
