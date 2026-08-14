<?php

declare(strict_types=1);

namespace App\Cases\Infrastructure;

use App\Cases\Domain\WorkflowTaskTemplate;
use App\Cases\Domain\WorkflowTaskTemplateRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineWorkflowTaskTemplateRepository implements WorkflowTaskTemplateRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param list<string> $territories
     * @return list<WorkflowTaskTemplate>
     */
    public function findApprovedForTerritoriesCatalogue(array $territories): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('template')
            ->from(WorkflowTaskTemplate::class, 'template')
            ->join('template.source', 'source')
            ->where('template.approved = true')
            ->andWhere('source.territory IN (:territories)')
            ->setParameter('territories', $territories, ArrayParameterType::STRING)
            ->orderBy('source.authority', 'ASC')
            ->addOrderBy('source.title', 'ASC')
            ->addOrderBy('template.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findApprovedForTerritories(Uuid $id, array $territories): ?WorkflowTaskTemplate
    {
        return $this->entityManager->createQueryBuilder()
            ->select('template')
            ->from(WorkflowTaskTemplate::class, 'template')
            ->join('template.source', 'source')
            ->where('template.id = :id')
            ->andWhere('template.approved = true')
            ->andWhere('source.territory IN (:territories)')
            ->setParameter('id', $id)
            ->setParameter('territories', $territories, ArrayParameterType::STRING)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
