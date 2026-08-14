<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use Symfony\Component\Uid\Uuid;

interface WorkflowTaskTemplateRepository
{
    /** @param list<string> $territories */
    public function findApprovedForTerritories(Uuid $id, array $territories): ?WorkflowTaskTemplate;

    /**
     * @param list<string> $territories
     * @return list<WorkflowTaskTemplate>
     */
    public function findApprovedForTerritoriesCatalogue(array $territories): array;
}
