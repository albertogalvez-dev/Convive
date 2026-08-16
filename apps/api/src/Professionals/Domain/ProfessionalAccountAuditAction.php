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
    case MembershipGranted = 'membership_granted';
    case MembershipRoleChanged = 'membership_role_changed';
    case MembershipSuspended = 'membership_suspended';
    case MembershipResumed = 'membership_resumed';
    case MembershipRemoved = 'membership_removed';
    case ProfileNameChanged = 'profile_name_changed';
    case ProfileEmailChanged = 'profile_email_changed';
    case EmailCorrected = 'email_corrected';
}
