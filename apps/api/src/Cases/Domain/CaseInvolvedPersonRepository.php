<?php
declare(strict_types=1);
namespace App\Cases\Domain;
use Symfony\Component\Uid\Uuid;
interface CaseInvolvedPersonRepository
{
    public function save(CaseInvolvedPerson $person): void;
    public function find(Uuid $id): ?CaseInvolvedPerson;
}
