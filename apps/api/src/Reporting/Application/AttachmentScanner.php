<?php

declare(strict_types=1);

namespace App\Reporting\Application;

/**
 * Receives the minimum possible input: one private object stream and returns
 * a bounded verdict. Implementations must not transmit report metadata.
 */
interface AttachmentScanner
{
    /** @param resource $content */
    public function scan($content): AttachmentScanVerdict;
}
