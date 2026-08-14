<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

use Symfony\Component\Uid\Uuid;

interface ProfessionalRepository
{
    public function find(Uuid $id): ?Professional;

    public function save(Professional $professional): void;

    public function findByEmail(ProfessionalEmail $email): ?Professional;
}
