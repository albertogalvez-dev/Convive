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
use Symfony\Component\Uid\Uuid;

final class ProfessionalProfileControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->client->disableReboot();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $connection = $this->entityManager->getConnection();
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $this->entityManager->clear();
        }

        parent::tearDown();
    }

    public function testTheProfileReportsTheAdministratorControlledContextWithoutOfferingToChangeIt(): void
    {
        [$professional, $organisation] = $this->createProfessional('profile-owner');
        $this->client->loginUser($professional);

        $this->client->request('GET', '/api/v1/professional/profile');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('no-store', (string) $this->client->getResponse()->headers->get('Cache-Control'));
        $payload = $this->responsePayload();
        self::assertSame($professional->name(), $payload['name']);
        self::assertSame($professional->email()->toString(), $payload['email']);
        self::assertSame($organisation->name(), $payload['memberships'][0]['organisation']['name']);
        self::assertSame('triage', $payload['memberships'][0]['role']);
        self::assertTrue($payload['memberships'][0]['managedByAdministrator']);
    }

    public function testAProfessionalCanCorrectTheirOwnName(): void
    {
        [$professional] = $this->createProfessional('profile-rename');
        $this->client->loginUser($professional);

        $this->client->jsonRequest('PATCH', '/api/v1/professional/profile', [
            'name' => 'Fictional Corrected Name',
            'email' => $professional->email()->toString(),
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame('Fictional Corrected Name', $this->responsePayload()['name']);
        // The email did not change, so the session survives.
        self::assertFalse($this->responsePayload()['sessionEnded']);
        $this->client->request('GET', '/api/v1/professional/profile');
        self::assertResponseIsSuccessful();
    }

    public function testChangingTheEmailEndsEverySessionBecauseItIsTheLoginIdentifier(): void
    {
        [$professional] = $this->createProfessional('profile-email');
        $revisionBefore = $professional->securityRevision();
        $this->client->loginUser($professional);

        $this->client->jsonRequest('PATCH', '/api/v1/professional/profile', [
            'name' => $professional->name(),
            'email' => 'profile-changed-'.Uuid::v7()->toRfc4122().'@profile-test.example',
        ]);

        self::assertResponseIsSuccessful();
        self::assertTrue($this->responsePayload()['sessionEnded']);
        self::assertGreaterThan($revisionBefore, $professional->securityRevision());
    }

    public function testAnEmailAlreadyUsedByAnotherProfessionalIsRejected(): void
    {
        [$professional, $organisation] = $this->createProfessional('profile-first');
        [$other] = $this->createProfessional('profile-second', $organisation);
        $this->client->loginUser($professional);

        $this->client->jsonRequest('PATCH', '/api/v1/professional/profile', [
            'name' => $professional->name(),
            'email' => $other->email()->toString(),
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertSame(
            'urn:convive:problem:professional-email-conflict',
            $this->responsePayload()['type'],
        );
    }

    public function testAnInvalidProfileIsRejected(): void
    {
        [$professional] = $this->createProfessional('profile-invalid');
        $this->client->loginUser($professional);

        foreach ([
            ['name' => '', 'email' => $professional->email()->toString()],
            ['name' => $professional->name(), 'email' => 'not-an-email'],
        ] as $payload) {
            $this->client->jsonRequest('PATCH', '/api/v1/professional/profile', $payload);
            self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function testTheProfileRequiresASession(): void
    {
        $this->client->request('GET', '/api/v1/professional/profile');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /** @return array{Professional, Organisation} */
    private function createProfessional(string $name, ?Organisation $organisation = null): array
    {
        if ($organisation === null) {
            $organisation = new Organisation(Uuid::v7(), 'Fictional Profile School', PublicReportingIdentifier::generate());
            $this->entityManager->persist($organisation);
        }

        $professional = new Professional(
            Uuid::v7(),
            ucfirst($name).' Professional',
            ProfessionalEmail::fromString($name.'-'.Uuid::v7()->toRfc4122().'@profile-test.example'),
            new DateTimeImmutable(),
        );
        $this->entityManager->persist($professional);
        $this->entityManager->persist(new OrganisationMembership(
            Uuid::v7(),
            $professional,
            $organisation,
            ProfessionalRole::Triage,
            new DateTimeImmutable(),
        ));
        $this->entityManager->flush();

        return [$professional, $organisation];
    }

    /** @return array<string, mixed> */
    private function responsePayload(): array
    {
        $content = $this->client->getResponse()->getContent();
        self::assertIsString($content);

        return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }
}
