<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

enum ProfessionalNotificationType: string
{
    case CaseAssigned = 'case_assigned';
    case CaseLifecycleChanged = 'case_lifecycle_changed';

    public function isRequired(): bool
    {
        return $this === self::CaseAssigned;
    }
}
