<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

interface ProfessionalAccountAuditEventRepository
{
    public function append(ProfessionalAccountAuditEvent $event): void;
}
