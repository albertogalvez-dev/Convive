<?php

declare(strict_types=1);

namespace App\Tests\Professionals\Infrastructure;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Organisations\Infrastructure\DoctrineOrganisationRepository;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRole;
use App\Professionals\Infrastructure\DoctrineOrganisationMembershipRepository;
use App\Professionals\Infrastructure\DoctrineProfessionalRepository;
use App\Tests\Shared\Infrastructure\Persistence\PostgreSqlTestCase;
use DateTimeImmutable;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Component\Uid\Uuid;

final class DoctrineOrganisationMembershipRepositoryTest extends PostgreSqlTestCase
{
    private DoctrineOrganisationRepository $organisationRepository;
    private DoctrineProfessionalRepository $professionalRepository;
    private DoctrineOrganisationMembershipRepository $membershipRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisationRepository = new DoctrineOrganisationRepository(
            $this->entityManager,
        );
        $this->professionalRepository = new DoctrineProfessionalRepository(
            $this->entityManager,
        );
        $this->membershipRepository = new DoctrineOrganisationMembershipRepository(
            $this->entityManager,
        );
    }

    public function testItSavesAndFindsAnActiveMembership(): void
    {
        $organisation = $this->createOrganisation();
        $professional = $this->createProfessional();

        $membership = new OrganisationMembership(
            Uuid::v7(),
            $professional,
            $organisation,
            ProfessionalRole::Triage,
            new DateTimeImmutable(),
        );
        $this->membershipRepository->save($membership);
        $this->entityManager->clear();

        $professional = $this->professionalRepository->findByEmail(
            $professional->email(),
        );
        $organisation = $this->organisationRepository
            ->findByPublicReportingIdentifier(
                $organisation->publicReportingIdentifier(),
            );

        $found = $this->membershipRepository
            ->findActiveByProfessionalAndOrganisation(
                $professional,
                $organisation,
                ProfessionalRole::Triage,
            );

        self::assertNotNull($found);
        self::assertSame(ProfessionalRole::Triage, $found->role());
    }

    public function testARevokedMembershipIsNoLongerFoundAsActive(): void
    {
        $organisation = $this->createOrganisation();
        $professional = $this->createProfessional();

        $membership = new OrganisationMembership(
            Uuid::v7(),
            $professional,
            $organisation,
            ProfessionalRole::Administrator,
            new DateTimeImmutable(),
        );
        $membership->revokeAt(new DateTimeImmutable());
        $this->membershipRepository->save($membership);
        $this->entityManager->clear();

        $found = $this->membershipRepository
            ->findActiveByProfessionalAndOrganisation(
                $this->professionalRepository->findByEmail(
                    $professional->email(),
                ),
                $this->organisationRepository
                    ->findByPublicReportingIdentifier(
                        $organisation->publicReportingIdentifier(),
                    ),
                ProfessionalRole::Administrator,
            );

        self::assertNull($found);
    }

    public function testFindActiveByProfessionalExcludesRevokedMemberships(): void
    {
        $organisation = $this->createOrganisation();
        $professional = $this->createProfessional();

        $active = new OrganisationMembership(
            Uuid::v7(),
            $professional,
            $organisation,
            ProfessionalRole::Triage,
            new DateTimeImmutable(),
        );
        $revoked = new OrganisationMembership(
            Uuid::v7(),
            $professional,
            $organisation,
            ProfessionalRole::Administrator,
            new DateTimeImmutable(),
        );
        $revoked->revokeAt(new DateTimeImmutable());

        $this->membershipRepository->save($active);
        $this->membershipRepository->save($revoked);
        $this->entityManager->clear();

        $professional = $this->professionalRepository->findByEmail(
            $professional->email(),
        );

        $memberships = $this->membershipRepository
            ->findActiveByProfessional($professional);

        self::assertCount(1, $memberships);
        self::assertSame(ProfessionalRole::Triage, $memberships[0]->role());
    }

    public function testItRejectsADuplicateActiveGrantForTheSameRole(): void
    {
        $organisation = $this->createOrganisation();
        $professional = $this->createProfessional();

        $this->membershipRepository->save(
            new OrganisationMembership(
                Uuid::v7(),
                $professional,
                $organisation,
                ProfessionalRole::Triage,
                new DateTimeImmutable(),
            ),
        );

        $this->expectException(DbalException::class);

        $this->membershipRepository->save(
            new OrganisationMembership(
                Uuid::v7(),
                $professional,
                $organisation,
                ProfessionalRole::Triage,
                new DateTimeImmutable(),
            ),
        );
    }

    private function createOrganisation(): Organisation
    {
        $organisation = new Organisation(
            Uuid::v7(),
            'IES Horizonte',
            PublicReportingIdentifier::generate(),
        );
        $this->organisationRepository->save($organisation);

        return $organisation;
    }

    private function createProfessional(): Professional
    {
        $professional = new Professional(
            Uuid::v7(),
            'Alex Rivera',
            ProfessionalEmail::fromString(
                sprintf('professional-%s@example.com', Uuid::v7()),
            ),
            new DateTimeImmutable(),
        );
        $this->professionalRepository->save($professional);

        return $professional;
    }
}
