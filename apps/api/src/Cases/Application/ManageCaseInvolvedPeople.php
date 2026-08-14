<?php
declare(strict_types=1);
namespace App\Cases\Application;
use App\Cases\Domain\CaseAuditAction;
use App\Cases\Domain\CaseAuditEvent;
use App\Cases\Domain\CaseAuditEventRepository;
use App\Cases\Domain\CaseAuditTarget;
use App\Cases\Domain\CaseInvolvedPerson;
use App\Cases\Domain\CaseInvolvedPersonName;
use App\Cases\Domain\CaseInvolvedPersonRepository;
use App\Cases\Domain\CaseInvolvedPersonRole;
use App\Cases\Domain\CasePermission;
use App\Cases\Domain\ManagedCase;
use App\Professionals\Domain\Professional;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;
final readonly class ManageCaseInvolvedPeople
{
    public function __construct(private AuthoriseCaseAccess $authorise, private CaseInvolvedPersonRepository $people, private CaseAuditEventRepository $auditEvents) {}
    public function add(ManagedCase $case, string $name, CaseInvolvedPersonRole $role, Professional $actor, DateTimeImmutable $now): CaseInvolvedPerson { $this->authorise->require($case, $actor, CasePermission::Manage); $person = new CaseInvolvedPerson(Uuid::v7(), $case, CaseInvolvedPersonName::fromString($name), $role, $actor, $now); $this->people->save($person); $this->audit($case, $actor, CaseAuditAction::PersonAdded, $person, $now); return $person; }
    public function correct(CaseInvolvedPerson $person, string $name, CaseInvolvedPersonRole $role, Professional $actor, DateTimeImmutable $now): void { $this->authorise->require($person->managedCase(), $actor, CasePermission::Manage); $person->correct(CaseInvolvedPersonName::fromString($name), $role, $now); $this->people->save($person); $this->audit($person->managedCase(), $actor, CaseAuditAction::PersonCorrected, $person, $now); }
    public function remove(CaseInvolvedPerson $person, Professional $actor, DateTimeImmutable $now): void { $this->authorise->require($person->managedCase(), $actor, CasePermission::Manage); $person->removeAt($now); $this->people->save($person); $this->audit($person->managedCase(), $actor, CaseAuditAction::PersonRemoved, $person, $now); }
    private function audit(ManagedCase $case, Professional $actor, CaseAuditAction $action, CaseInvolvedPerson $person, DateTimeImmutable $now): void { $this->auditEvents->append(new CaseAuditEvent(Uuid::v7(), $case, $actor, $action, CaseAuditTarget::Person, $person->id(), $now)); $this->auditEvents->flush(); }
}
