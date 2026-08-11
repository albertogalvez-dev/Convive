<?php

declare(strict_types=1);

namespace App\Cases\Domain;

enum ProfessionalExportKind: string
{
    case OperationalOverview = 'operational_overview';
}
