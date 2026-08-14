<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class HandoverProfessionalCaseAssignmentRequest
{
    public function __construct(
        #[Assert\Uuid]
        public string $professionalId,
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 500)]
        public string $reason,
    ) {
    }
}
