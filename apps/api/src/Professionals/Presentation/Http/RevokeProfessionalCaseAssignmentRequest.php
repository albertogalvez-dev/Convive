<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RevokeProfessionalCaseAssignmentRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 500)]
        public string $reason,
    ) {
    }
}
