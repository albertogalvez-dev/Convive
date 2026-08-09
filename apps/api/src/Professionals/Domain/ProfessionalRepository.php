<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

interface ProfessionalRepository
{
    public function save(Professional $professional): void;

    public function findByEmail(ProfessionalEmail $email): ?Professional;
}
