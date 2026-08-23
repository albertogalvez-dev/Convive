<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use Symfony\Component\Validator\Constraints as Assert;

#[\OpenApi\Attributes\Schema(additionalProperties: false)]
final readonly class TransitionManagedCaseRequest
{
    public function __construct(
        #[Assert\Choice(choices: ['active', 'closed'])]
        public string $status,
        #[Assert\NotBlank]
        #[Assert\Length(max: 500)]
        public string $reason,
        #[Assert\NotBlank]
        #[Assert\Length(max: 500)]
        public string $evidence,
    ) {
    }
}
