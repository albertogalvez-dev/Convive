<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

enum ReporterRecurrence: string
{
    case Single = 'single';
    case Repeated = 'repeated';
    case Ongoing = 'ongoing';
    case Unknown = 'unknown';
}
