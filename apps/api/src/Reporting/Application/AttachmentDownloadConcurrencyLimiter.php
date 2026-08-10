<?php

declare(strict_types=1);

namespace App\Reporting\Application;

interface AttachmentDownloadConcurrencyLimiter
{
    public function acquire(): AttachmentDownloadPermit;
}
