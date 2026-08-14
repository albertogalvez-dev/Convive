<?php

declare(strict_types=1);

namespace App\Cases\Infrastructure;

use App\Cases\Domain\CaseCommunication;
use App\Cases\Domain\CaseCommunicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineCaseCommunicationRepository implements CaseCommunicationRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(CaseCommunication $communication): void
    {
        $this->entityManager->persist($communication);
        $this->entityManager->flush();
    }

    public function find(Uuid $id): ?CaseCommunication
    {
        $communication = $this->entityManager->find(CaseCommunication::class, $id);

        return $communication instanceof CaseCommunication ? $communication : null;
    }
}
