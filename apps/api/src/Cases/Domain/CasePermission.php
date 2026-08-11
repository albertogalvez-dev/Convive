<?php

declare(strict_types=1);

namespace App\Cases\Domain;

enum CasePermission: string
{
    case View = 'view';
    case Manage = 'manage';
    case ManageAssignments = 'manage_assignments';
}
