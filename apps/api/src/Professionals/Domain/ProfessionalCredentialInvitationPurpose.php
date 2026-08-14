<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

enum ProfessionalCredentialInvitationPurpose: string
{
    case Activation = 'activation';
    case PasswordReset = 'password_reset';
}
