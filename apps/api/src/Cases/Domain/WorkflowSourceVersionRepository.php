<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use Symfony\Component\Uid\Uuid;

interface WorkflowSourceVersionRepository
{
    public function find(Uuid $id): ?WorkflowSourceVersion;
}
