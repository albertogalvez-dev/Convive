<?php

declare(strict_types=1);

namespace App\Professionals\Infrastructure;

use App\Professionals\Domain\ProfessionalAccountAuditEvent;
use App\Professionals\Domain\ProfessionalAccountAuditEventRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineProfessionalAccountAuditEventRepository implements ProfessionalAccountAuditEventRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function append(ProfessionalAccountAuditEvent $event): void
    {
        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }
}
