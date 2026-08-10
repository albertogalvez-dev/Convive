<?php

declare(strict_types=1);

namespace App\Reporting\Application;

enum AttachmentScanVerdict
{
    case Clean;
    case Infected;
    case Unavailable;
}
