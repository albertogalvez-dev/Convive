<?php

declare(strict_types=1);

namespace App\Reporting\Application;

enum PublicReportingMode: string
{
    case Operational = 'operational';
    case FictionalDemo = 'fictional_demo';
    case Disabled = 'disabled';
}
