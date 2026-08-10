<?php

declare(strict_types=1);

namespace App\Reporting\Application;

interface AttachmentDownloadPermit
{
    public function release(): void;
}
