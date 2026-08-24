<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use Symfony\Component\Validator\Constraints as Assert;

#[\OpenApi\Attributes\Schema(additionalProperties: false)]
final readonly class InviteProfessionalRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 160)]
        public string $name,
        #[Assert\NotBlank]
        #[Assert\Email(mode: 'strict')]
        #[Assert\Length(max: 255)]
        public string $email,
        #[Assert\Choice(choices: ['triage', 'administrator'])]
        public string $role,
    ) {
    }
}
