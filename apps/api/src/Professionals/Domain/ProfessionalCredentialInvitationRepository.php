<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

interface ProfessionalCredentialInvitationRepository
{
    public function save(ProfessionalCredentialInvitation $invitation): void;

    public function findBySecret(string $secret): ?ProfessionalCredentialInvitation;
}
