<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

#[\OpenApi\Attributes\Schema(additionalProperties: false)]
final readonly class VerifyReportAccessRequest
{
    public function __construct(
        public string $accessSecret,
    ) {
    }
}
