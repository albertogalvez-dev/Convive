<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

#[\OpenApi\Attributes\Schema(additionalProperties: false)]
final readonly class RespondToProfessionalReportRequest
{
    public function __construct(public string $content)
    {
    }
}
