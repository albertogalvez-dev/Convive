<?php

declare(strict_types=1);

namespace App\Organisations\Application\GetPublicReportingProfile;

use App\Organisations\Domain\OrganisationRepository;
use App\Organisations\Domain\PublicReportingIdentifier;

final readonly class GetPublicReportingProfile
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
    ) {
    }

    public function __invoke(
        PublicReportingIdentifier $identifier,
    ): PublicReportingProfile {
        $organisation = $this->organisationRepository
            ->findByPublicReportingIdentifier($identifier);

        if ($organisation === null) {
            throw PublicReportingOrganisationNotFound::withIdentifier(
                $identifier,
            );
        }

        return new PublicReportingProfile(
            $organisation->name(),
        );
    }
}
