<?php

declare(strict_types=1);

namespace App\Cases\Infrastructure;

use App\Cases\Domain\CaseTask;
use App\Cases\Domain\CaseTaskRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineCaseTaskRepository implements CaseTaskRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function find(\Symfony\Component\Uid\Uuid $id): ?CaseTask
    {
        return $this->entityManager->find(CaseTask::class, $id);
    }

    public function save(CaseTask $task): void
    {
        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }
}
