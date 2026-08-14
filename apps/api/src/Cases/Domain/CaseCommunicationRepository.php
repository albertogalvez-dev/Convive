<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use Symfony\Component\Uid\Uuid;

interface CaseCommunicationRepository
{
    public function save(CaseCommunication $communication): void;

    public function find(Uuid $id): ?CaseCommunication;
}
