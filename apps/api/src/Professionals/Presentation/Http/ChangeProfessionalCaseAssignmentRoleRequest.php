<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use Symfony\Component\Validator\Constraints as Assert;

#[\OpenApi\Attributes\Schema(additionalProperties: false)]
final readonly class ChangeProfessionalCaseAssignmentRoleRequest
{
    public function __construct(
        #[Assert\Choice(choices: ['contributor', 'observer'])]
        public string $role,
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 500)]
        public string $reason,
    ) {
    }
}
