<?php

declare(strict_types=1);

namespace App\Tests\Professionals\Presentation\Http;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRole;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class OrganisationMembershipControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
    }

    public function testAdministratorSuspendsMembershipWithoutRemovingHistoricalCaseAssignmentBoundary(): void
    {
        $organisation = new Organisation(Uuid::v7(), 'Fictional Membership School', PublicReportingIdentifier::generate());
        $administrator = $this->professional('membership-administrator');
        $target = $this->professional('membership-target');
        $membership = new OrganisationMembership(Uuid::v7(), $target, $organisation, ProfessionalRole::Triage, new DateTimeImmutable());
        $this->entityManager->persist($organisation);
        $this->entityManager->persist($administrator);
        $this->entityManager->persist($target);
        $this->entityManager->persist(new OrganisationMembership(Uuid::v7(), $administrator, $organisation, ProfessionalRole::Administrator, new DateTimeImmutable()));
        $this->entityManager->persist($membership);
        $this->entityManager->flush();
        $this->client->loginUser($administrator);

        $endpoint = '/api/v1/professional/organisations/'.$organisation->id()->toRfc4122().'/memberships/'.$membership->id()->toRfc4122();
        $this->client->jsonRequest('PATCH', $endpoint, ['action' => 'suspend'], $this->sameOriginHeaders());

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('suspended', $response['state']);
        self::assertArrayNotHasKey('assignments', $response);
        self::assertArrayNotHasKey('cases', $response);
    }

    public function testCrossOrganisationAdministratorCannotReadOrMutateMemberships(): void
    {
        $organisation = new Organisation(Uuid::v7(), 'Fictional Target School', PublicReportingIdentifier::generate());
        $other = new Organisation(Uuid::v7(), 'Fictional Other School', PublicReportingIdentifier::generate());
        $administrator = $this->professional('foreign-administrator');
        $this->entityManager->persist($organisation);
        $this->entityManager->persist($other);
        $this->entityManager->persist($administrator);
        $this->entityManager->persist(new OrganisationMembership(Uuid::v7(), $administrator, $other, ProfessionalRole::Administrator, new DateTimeImmutable()));
        $this->entityManager->flush();
        $this->client->loginUser($administrator);

        $this->client->request('GET', '/api/v1/professional/organisations/'.$organisation->id()->toRfc4122().'/memberships');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function professional(string $name): Professional
    {
        $professional = new Professional(Uuid::v7(), 'Fictional '.$name, ProfessionalEmail::fromString($name.'-'.Uuid::v7()->toRfc4122().'@example.invalid'), new DateTimeImmutable());
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $hasher);
        $professional->replacePasswordHash($hasher->hashPassword($professional, 'fictional secure password'));
        return $professional;
    }

    /** @return array<string, string> */
    private function sameOriginHeaders(): array
    {
        return ['HTTP_ORIGIN' => 'http://localhost', 'HTTP_SEC_FETCH_SITE' => 'same-origin'];
    }
}
