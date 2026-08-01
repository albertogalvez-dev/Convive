<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

enum ReportStatus: string
{
    case Received = 'received';
}
