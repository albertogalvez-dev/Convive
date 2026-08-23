<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use Symfony\Component\Validator\Constraints as Assert;

#[\OpenApi\Attributes\Schema(additionalProperties: false)]
final readonly class VerifyReporterEmailRequest
{
    public function __construct(
        #[Assert\Regex(pattern: '/^[0-9a-f]{64}$/D')]
        public string $token,
    ) {
    }
}
