<?php

declare(strict_types=1);

namespace App\Cases\Domain;

enum CaseOperationalView: string
{
    case Assigned = 'assigned';
    case Overdue = 'overdue';
    case Upcoming = 'upcoming';
    case Recent = 'recent';
}
