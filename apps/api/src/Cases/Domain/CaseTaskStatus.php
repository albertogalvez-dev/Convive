<?php

declare(strict_types=1);

namespace App\Cases\Domain;

enum CaseTaskStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case NotApplicable = 'not_applicable';
}
