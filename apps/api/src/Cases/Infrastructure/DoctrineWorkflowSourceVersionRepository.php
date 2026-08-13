<?php

declare(strict_types=1);

namespace App\Cases\Infrastructure;

use App\Cases\Domain\WorkflowSourceVersion;
use App\Cases\Domain\WorkflowSourceVersionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineWorkflowSourceVersionRepository implements WorkflowSourceVersionRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function find(Uuid $id): ?WorkflowSourceVersion
    {
        return $this->entityManager->find(WorkflowSourceVersion::class, $id);
    }
}
