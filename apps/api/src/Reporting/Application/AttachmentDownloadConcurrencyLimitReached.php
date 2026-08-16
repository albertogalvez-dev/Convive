<?php

declare(strict_types=1);

namespace App\Reporting\Application;

use RuntimeException;

final class AttachmentDownloadConcurrencyLimitReached extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The private attachment download capacity is temporarily full.');
    }
}
