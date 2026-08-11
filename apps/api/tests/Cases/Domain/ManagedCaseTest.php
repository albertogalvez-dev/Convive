<?php

declare(strict_types=1);

namespace App\Tests\Cases\Domain;

use App\Cases\Domain\CaseAssignment;
use App\Cases\Domain\CaseAssignmentRole;
use App\Cases\Domain\CaseInvolvedPerson;
use App\Cases\Domain\CaseInvolvedPersonName;
use App\Cases\Domain\CaseInvolvedPersonRole;
use App\Cases\Domain\CaseModality;
use App\Cases\Domain\CasePermission;
use App\Cases\Domain\CaseStatus;
use App\Cases\Domain\ManagedCase;
use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ManagedCaseTest extends TestCase
{
    public function testANewCaseHasAnExplicitAssessmentStateAndModality(): void
    {
        $professional = $this->professional('lead');
        $managedCase = $this->managedCase($professional, CaseModality::Mixed);

        self::assertSame(CaseStatus::Assessment, $managedCase->status());
        self::assertSame(CaseModality::Mixed, $managedCase->modality());
    }

    #[DataProvider('assignmentPermissions')]
    public function testAssignmentRolesExposeOnlyTheirOperationalPermissions(
        CaseAssignmentRole $role,
        CasePermission $permission,
        bool $expected,
    ): void {
        $professional = $this->professional($role->value);
        $assignment = new CaseAssignment(
            Uuid::v7(),
            $this->managedCase($professional),
            $professional,
            $role,
            $professional,
            new DateTimeImmutable('2026-08-11T10:00:00+00:00'),
        );

        self::assertSame($expected, $assignment->permits($permission));
    }

    /** @return iterable<string, array{CaseAssignmentRole, CasePermission, bool}> */
    public static function assignmentPermissions(): iterable
    {
        yield 'lead manages assignments' => [CaseAssignmentRole::Lead, CasePermission::ManageAssignments, true];
        yield 'contributor manages content' => [CaseAssignmentRole::Contributor, CasePermission::Manage, true];
        yield 'contributor cannot assign' => [CaseAssignmentRole::Contributor, CasePermission::ManageAssignments, false];
        yield 'observer views' => [CaseAssignmentRole::Observer, CasePermission::View, true];
        yield 'observer cannot manage' => [CaseAssignmentRole::Observer, CasePermission::Manage, false];
    }

    public function testARevokedAssignmentGrantsNothing(): void
    {
        $professional = $this->professional('revoked');
        $assignment = new CaseAssignment(
            Uuid::v7(),
            $this->managedCase($professional),
            $professional,
            CaseAssignmentRole::Lead,
            $professional,
            new DateTimeImmutable('2026-08-11T10:00:00+00:00'),
        );
        $assignment->revokeAt(new DateTimeImmutable('2026-08-11T11:00:00+00:00'));

        self::assertFalse($assignment->permits(CasePermission::View));
    }

    public function testAnAssignmentCannotBeRevokedBeforeItWasAssigned(): void
    {
        $professional = $this->professional('invalid-revocation');
        $assignment = new CaseAssignment(
            Uuid::v7(),
            $this->managedCase($professional),
            $professional,
            CaseAssignmentRole::Lead,
            $professional,
            new DateTimeImmutable('2026-08-11T10:00:00+00:00'),
        );

        $this->expectException(LogicException::class);
        $assignment->revokeAt(new DateTimeImmutable('2026-08-11T09:59:59+00:00'));
    }

    public function testAnInvolvedPersonStoresOnlyBoundedOperationalIdentity(): void
    {
        $professional = $this->professional('person');
        $person = new CaseInvolvedPerson(
            Uuid::v7(),
            $this->managedCase($professional),
            CaseInvolvedPersonName::fromString("  Persona   ficticia A  "),
            CaseInvolvedPersonRole::Affected,
            $professional,
            new DateTimeImmutable('2026-08-11T10:00:00+00:00'),
        );

        self::assertSame('Persona ficticia A', $person->name()->toString());
        self::assertSame(CaseInvolvedPersonRole::Affected, $person->role());
    }

    public function testAnEmptyInvolvedPersonNameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CaseInvolvedPersonName::fromString('  ');
    }

    private function managedCase(Professional $professional, CaseModality $modality = CaseModality::Unknown): ManagedCase
    {
        return new ManagedCase(
            Uuid::v7(),
            $this->organisation(),
            $professional,
            new DateTimeImmutable('2026-08-11T10:00:00+00:00'),
            $modality,
        );
    }

    private function professional(string $suffix): Professional
    {
        return new Professional(
            Uuid::v7(),
            'Fictional Professional',
            ProfessionalEmail::fromString($suffix.'@example.invalid'),
            new DateTimeImmutable('2026-08-11T09:00:00+00:00'),
        );
    }

    private function organisation(): Organisation
    {
        return new Organisation(
            Uuid::v7(),
            'Fictional Case School',
            PublicReportingIdentifier::generate(),
        );
    }
}
