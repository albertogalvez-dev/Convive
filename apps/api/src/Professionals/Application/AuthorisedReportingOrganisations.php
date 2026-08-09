<?php

declare(strict_types=1);

namespace App\Professionals\Application;

use App\Organisations\Domain\Organisation;
use App\Professionals\Domain\OrganisationMembershipRepository;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalRole;

final readonly class AuthorisedReportingOrganisations
{
    public function __construct(private OrganisationMembershipRepository $memberships)
    {
    }

    /** @return list<Organisation> */
    public function __invoke(Professional $professional): array
    {
        $organisations = [];

        foreach ($this->memberships->findActiveByProfessional($professional) as $membership) {
            if ($membership->role() !== ProfessionalRole::Triage) {
                continue;
            }

            $organisation = $membership->organisation();
            $organisations[$organisation->id()->toRfc4122()] = $organisation;
        }

        return array_values($organisations);
    }
}
