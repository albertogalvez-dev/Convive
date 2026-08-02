<?php

declare(strict_types=1);

namespace App\Organisations\Domain;

use Symfony\Component\Uid\Uuid;

interface OrganisationRepository
{
    public function save(Organisation $organisation): void;

    public function findById(Uuid $id): ?Organisation;

    public function findByPublicReportingIdentifier(
        PublicReportingIdentifier $identifier,
    ): ?Organisation;
}
