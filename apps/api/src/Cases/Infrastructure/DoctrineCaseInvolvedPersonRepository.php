<?php
declare(strict_types=1);
namespace App\Cases\Infrastructure;
use App\Cases\Domain\CaseInvolvedPerson;
use App\Cases\Domain\CaseInvolvedPersonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
final readonly class DoctrineCaseInvolvedPersonRepository implements CaseInvolvedPersonRepository
{
    public function __construct(private EntityManagerInterface $entityManager) {}
    public function save(CaseInvolvedPerson $person): void { $this->entityManager->persist($person); $this->entityManager->flush(); }
    public function find(Uuid $id): ?CaseInvolvedPerson { $person = $this->entityManager->find(CaseInvolvedPerson::class, $id); return $person instanceof CaseInvolvedPerson ? $person : null; }
}
