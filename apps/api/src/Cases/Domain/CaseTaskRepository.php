<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use Symfony\Component\Uid\Uuid;

interface CaseTaskRepository
{
    public function find(Uuid $id): ?CaseTask;

    public function save(CaseTask $task): void;
}
