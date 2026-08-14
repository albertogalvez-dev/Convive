<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

enum ProfessionalAccountAuditAction: string
{
    case Invited = 'invited';
    case CredentialAccepted = 'credential_accepted';
    case PasswordResetIssued = 'password_reset_issued';
    case Suspended = 'suspended';
    case Reactivated = 'reactivated';
    case Deactivated = 'deactivated';
}
