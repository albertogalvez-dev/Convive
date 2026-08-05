<?php

declare(strict_types=1);

namespace App\Organisations\Application\GetPublicReportingProfile;

use App\Organisations\Domain\PublicReportingIdentifier;
use RuntimeException;

final class PublicReportingOrganisationNotFound extends RuntimeException
{
    public static function withIdentifier(
        PublicReportingIdentifier $identifier,
    ): self {
        return new self(
            sprintf(
                'No reporting organisation was found for identifier "%s".',
                $identifier->toString(),
            ),
        );
    }
}
