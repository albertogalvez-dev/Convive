<?php

declare(strict_types=1);

namespace App\Professionals\Application;

use App\Professionals\Domain\Professional;

final readonly class ProfessionalCredentialResult
{
    public function __construct(
        public Professional $professional,
        /** This one-time value is deliberately never persisted or logged. */
        public string $secret,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
