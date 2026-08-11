<?php

declare(strict_types=1);

namespace App\Cases\Domain;

enum CasePermission: string
{
    case View = 'view';
    case Export = 'export';
    case ViewAudit = 'view_audit';
    case Manage = 'manage';
    case ManageAssignments = 'manage_assignments';
}
