<?php

declare(strict_types=1);

namespace App\Cases\Domain;

enum CaseStatus: string
{
    case Assessment = 'assessment';
    case Active = 'active';
    case Closed = 'closed';
}
