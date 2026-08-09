<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

final readonly class RespondToProfessionalReportRequest
{
    public function __construct(public string $content)
    {
    }
}
