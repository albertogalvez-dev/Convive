<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use Symfony\Component\Validator\Constraints as Assert;

#[\OpenApi\Attributes\Schema(additionalProperties: false)]
final readonly class ManageOrganisationMembershipRequest
{
    public function __construct(
        #[Assert\Choice(choices: ['triage', 'administrator'])]
        public string $role,
    ) {
    }
}
