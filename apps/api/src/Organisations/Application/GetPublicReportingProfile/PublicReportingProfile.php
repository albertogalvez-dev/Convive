<?php

declare(strict_types=1);

namespace App\Organisations\Application\GetPublicReportingProfile;

final readonly class PublicReportingProfile
{
    public function __construct(
        public string $name,
    ) {
    }
}
