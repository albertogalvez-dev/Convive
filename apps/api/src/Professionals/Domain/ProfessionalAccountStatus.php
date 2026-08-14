<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

enum ProfessionalAccountStatus: string
{
    case Invited = 'invited';
    case Active = 'active';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';

    public function permitsAuthentication(): bool
    {
        return $this === self::Active;
    }
}
