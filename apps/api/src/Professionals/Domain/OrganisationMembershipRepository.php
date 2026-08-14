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

    public function hasActiveMembership(Professional $professional, Organisation $organisation): bool;

    /** @return list<OrganisationMembership> */
    public function findActiveByOrganisation(Organisation $organisation): array;

    /** @return list<OrganisationMembership> */
    public function findByOrganisation(Organisation $organisation): array;

    public function findActiveByProfessionalAndOrganisation(
        Professional $professional,
        Organisation $organisation,
        ProfessionalRole $role,
    ): ?OrganisationMembership;
}
