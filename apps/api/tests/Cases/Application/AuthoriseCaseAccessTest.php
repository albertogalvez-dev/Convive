<?php

declare(strict_types=1);

namespace App\Tests\Cases\Application;

use App\Cases\Application\AuthoriseCaseAccess;
use App\Cases\Domain\CaseAccessDenied;
use App\Cases\Domain\CaseAssignment;
use App\Cases\Domain\CaseAssignmentRepository;
use App\Cases\Domain\CaseAssignmentRole;
use App\Cases\Domain\CaseModality;
use App\Cases\Domain\CasePermission;
use App\Cases\Domain\ManagedCase;
use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\OrganisationMembershipRepository;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRole;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AuthoriseCaseAccessTest extends TestCase
{
    public function testAnActiveMemberWithTheRequiredCaseAssignmentIsAuthorised(): void
    {
        [$managedCase, $professional, $membership, $assignment] = $this->scope(
            ProfessionalRole::Triage,
            CaseAssignmentRole::Contributor,
        );

        $authoriser = new AuthoriseCaseAccess(
            $this->memberships([$membership]),
            $this->assignments([$assignment]),
        );

        self::assertSame(
            $assignment,
            $authoriser->require($managedCase, $professional, CasePermission::Manage),
        );
    }

    public function testAdministrationAloneDoesNotGrantCaseContentAccess(): void
    {
        [$managedCase, $professional, $membership] = $this->scope(
            ProfessionalRole::Administrator,
            CaseAssignmentRole::Observer,
        );

        $authoriser = new AuthoriseCaseAccess(
            $this->memberships([$membership]),
            $this->assignments([]),
        );

        $this->expectException(CaseAccessDenied::class);
        $authoriser->require($managedCase, $professional, CasePermission::View);
    }

    public function testAnAssignmentCannotCrossTheOrganisationBoundary(): void
    {
        [$managedCase, $professional, , $assignment] = $this->scope(
            ProfessionalRole::Triage,
            CaseAssignmentRole::Lead,
        );
        $foreignMembership = new OrganisationMembership(
            Uuid::v7(),
            $professional,
            $this->organisation(),
            ProfessionalRole::Triage,
            new DateTimeImmutable(),
        );

        $authoriser = new AuthoriseCaseAccess(
            $this->memberships([$foreignMembership]),
            $this->assignments([$assignment]),
        );

        $this->expectException(CaseAccessDenied::class);
        $authoriser->require($managedCase, $professional, CasePermission::View);
    }

    public function testAnAssignmentForAnotherCaseDoesNotGrantAccess(): void
    {
        [$managedCase, $professional, $membership] = $this->scope(
            ProfessionalRole::Triage,
            CaseAssignmentRole::Lead,
        );
        $otherCase = new ManagedCase(
            Uuid::v7(),
            $managedCase->organisation(),
            $professional,
            new DateTimeImmutable(),
            CaseModality::Unknown,
        );
        $otherAssignment = new CaseAssignment(
            Uuid::v7(),
            $otherCase,
            $professional,
            CaseAssignmentRole::Lead,
            $professional,
            new DateTimeImmutable(),
        );

        $authoriser = new AuthoriseCaseAccess(
            $this->memberships([$membership]),
            $this->assignments([$otherAssignment]),
        );

        $this->expectException(CaseAccessDenied::class);
        $authoriser->require($managedCase, $professional, CasePermission::View);
    }

    /**
     * @return array{ManagedCase, Professional, OrganisationMembership, CaseAssignment}
     */
    private function scope(ProfessionalRole $organisationRole, CaseAssignmentRole $caseRole): array
    {
        $organisation = $this->organisation();
        $professional = $this->professional();
        $managedCase = new ManagedCase(
            Uuid::v7(),
            $organisation,
            $professional,
            new DateTimeImmutable(),
            CaseModality::Digital,
        );
        $membership = new OrganisationMembership(
            Uuid::v7(),
            $professional,
            $organisation,
            $organisationRole,
            new DateTimeImmutable(),
        );
        $assignment = new CaseAssignment(
            Uuid::v7(),
            $managedCase,
            $professional,
            $caseRole,
            $professional,
            new DateTimeImmutable(),
        );

        return [$managedCase, $professional, $membership, $assignment];
    }

    /** @param list<OrganisationMembership> $memberships */
    private function memberships(array $memberships): OrganisationMembershipRepository
    {
        $repository = $this->createStub(OrganisationMembershipRepository::class);
        $repository->method('findActiveByProfessional')->willReturn($memberships);

        return $repository;
    }

    /** @param list<CaseAssignment> $assignments */
    private function assignments(array $assignments): CaseAssignmentRepository
    {
        return new class($assignments) implements CaseAssignmentRepository {
            /** @param list<CaseAssignment> $assignments */
            public function __construct(private array $assignments)
            {
            }

            public function findActive(ManagedCase $managedCase, Professional $professional): ?CaseAssignment
            {
                foreach ($this->assignments as $assignment) {
                    if (
                        $assignment->managedCase()->id()->equals($managedCase->id())
                        && $assignment->professional()->id()->equals($professional->id())
                        && $assignment->isActive()
                    ) {
                        return $assignment;
                    }
                }

                return null;
            }
        };
    }

    private function organisation(): Organisation
    {
        return new Organisation(Uuid::v7(), 'Fictional School', PublicReportingIdentifier::generate());
    }

    private function professional(): Professional
    {
        return new Professional(
            Uuid::v7(),
            'Fictional Professional',
            ProfessionalEmail::fromString(Uuid::v7()->toRfc4122().'@example.invalid'),
            new DateTimeImmutable(),
        );
    }
}
