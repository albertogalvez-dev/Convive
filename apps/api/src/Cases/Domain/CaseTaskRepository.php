<?php

declare(strict_types=1);

namespace App\Cases\Domain;

interface CaseTaskRepository
{
    public function save(CaseTask $task): void;
}
