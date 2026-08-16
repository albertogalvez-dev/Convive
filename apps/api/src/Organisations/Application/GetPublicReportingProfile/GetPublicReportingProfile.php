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

        // A paused or retired channel is refused exactly like an identifier
        // that never existed, so the state of a real centre — and whether it
        // ever had a link — is not observable from outside.
        if ($organisation === null || !$organisation->acceptsNewReports()) {
            throw PublicReportingOrganisationNotFound::withIdentifier(
                $identifier,
            );
        }

        return new PublicReportingProfile(
            $organisation->name(),
        );
    }
}
