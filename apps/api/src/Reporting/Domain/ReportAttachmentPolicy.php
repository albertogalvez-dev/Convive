<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

final class ReportAttachmentPolicy
{
    public const int MAXIMUM_FILE_BYTES = 5 * 1024 * 1024;
    public const int MAXIMUM_ATTACHMENTS_PER_WRITE = 3;
    public const int MAXIMUM_ATTACHMENTS_PER_REPORT = 10;
    public const int MAXIMUM_REPORT_ATTACHMENT_BYTES = 20 * 1024 * 1024;
    public const int MAXIMUM_REQUEST_BYTES = 16 * 1024 * 1024;

    private function __construct()
    {
    }
}
