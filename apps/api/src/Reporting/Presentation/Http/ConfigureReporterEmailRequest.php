<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use Symfony\Component\Validator\Constraints as Assert;

#[\OpenApi\Attributes\Schema(additionalProperties: false)]
final readonly class ConfigureReporterEmailRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email(mode: 'strict')]
        #[Assert\Length(max: 254)]
        public string $email,
        #[Assert\IsTrue]
        public bool $consentAccepted,
    ) {
    }
}
