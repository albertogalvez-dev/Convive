<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AcceptProfessionalCredentialRequest
{
    public function __construct(
        #[Assert\Regex(pattern: '/^[a-f0-9]{64}$/D')]
        public string $secret,
        #[Assert\Length(min: 15, max: 255)]
        public string $password,
    ) {
    }
}
