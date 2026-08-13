<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

final readonly class ReviewProfessionalReportRequest
{
    public function __construct(
        public string $reason,
        public string $professionalConcernCategory = 'unknown',
        public string $professionalRecurrence = 'unknown',
        public string $professionalAttentionCue = 'unknown',
    )
    {
    }
}
