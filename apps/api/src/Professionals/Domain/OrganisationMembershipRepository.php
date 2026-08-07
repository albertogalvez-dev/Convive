<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

use App\Organisations\Domain\Organisation;

interface OrganisationMembershipRepository
{
    public function save(OrganisationMembership $membership): void;

    /**
     * @return list<OrganisationMembership>
     */
    public function findActiveByProfessional(Professional $professional): array;

    public function findActiveByProfessionalAndOrganisation(
        Professional $professional,
        Organisation $organisation,
        ProfessionalRole $role,
    ): ?OrganisationMembership;
}
