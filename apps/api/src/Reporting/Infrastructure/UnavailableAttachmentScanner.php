<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure;

use App\Reporting\Application\AttachmentScanner;
use App\Reporting\Application\AttachmentScanVerdict;

/**
 * Safe default until an isolated scanner is provisioned. A file can never
 * become readable merely because a scanner is unavailable.
 */
final class UnavailableAttachmentScanner implements AttachmentScanner
{
    public function scan($content): AttachmentScanVerdict
    {
        return AttachmentScanVerdict::Unavailable;
    }
}
