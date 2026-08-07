<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

final readonly class AddReportFollowUpEntryRequest
{
    public function __construct(
        public string $content,
    ) {
    }
}
