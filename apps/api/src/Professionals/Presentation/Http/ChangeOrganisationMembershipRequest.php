<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChangeOrganisationMembershipRequest
{
    public function __construct(
        #[Assert\Choice(choices: ['triage', 'administrator'])]
        public ?string $role = null,
        #[Assert\Choice(choices: ['suspend', 'resume', 'remove'])]
        public ?string $action = null,
    ) {
    }
}
