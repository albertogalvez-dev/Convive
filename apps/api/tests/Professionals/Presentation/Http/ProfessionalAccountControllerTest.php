<?php

declare(strict_types=1);

namespace App\Tests\Professionals\Presentation\Http;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalAccountStatus;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRole;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class ProfessionalAccountControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
        $this->client->disableReboot();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        $rateLimiterCache = self::getContainer()->get('cache.rate_limiter');
        self::assertInstanceOf(CacheItemPoolInterface::class, $rateLimiterCache);
        $rateLimiterCache->clear();
    }

    public function testAdministratorCanInviteAndActivateWithoutGivingCaseContent(): void
    {
        [$organisation, $administrator] = $this->administratorScope();
        $this->client->loginUser($administrator);
        $base = '/api/v1/professional/organisations/'.$organisation->id()->toRfc4122().'/accounts';

        $this->client->jsonRequest(
            'POST',
            $base,
            [
                'name' => 'Fictional invited professional',
                'email' => 'invited-'.Uuid::v7()->toRfc4122().'@example.invalid',
                'role' => 'triage',
            ],
            $this->sameOriginHeaders(),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('invited', $payload['professional']['status']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $payload['credential']['secret']);

        $this->client->jsonRequest(
            'POST',
            '/api/v1/professional/account-credentials/accept',
            [
                'secret' => $payload['credential']['secret'],
                'password' => 'fictional secure password',
            ],
            $this->sameOriginHeaders(),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->entityManager->clear();
        $this->client->request('GET', $base);
        self::assertResponseIsSuccessful();
        $items = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['items'];
        self::assertContains('active', array_column($items, 'status'));
        self::assertArrayNotHasKey('assignments', $items[0]);
    }

    public function testCrossOrganisationProfessionalCannotAdministerAccountsAndUnknownCredentialIsUnavailable(): void
    {
        [$organisation] = $this->administratorScope();
        $otherOrganisation = new Organisation(Uuid::v7(), 'Other Fictional School', PublicReportingIdentifier::generate());
        $triage = $this->professional('cross-organisation-triage');
        $this->entityManager->persist($otherOrganisation);
        $this->entityManager->persist($triage);
        $this->entityManager->persist(new OrganisationMembership(Uuid::v7(), $triage, $otherOrganisation, ProfessionalRole::Triage, new DateTimeImmutable()));
        $this->entityManager->flush();
        $this->client->loginUser($triage);
        $this->client->request('GET', '/api/v1/professional/organisations/'.$organisation->id()->toRfc4122().'/accounts');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $this->client->jsonRequest(
            'POST',
            '/api/v1/professional/account-credentials/accept',
            [
                'secret' => str_repeat('a', 64),
                'password' => 'fictional secure password',
            ],
            $this->sameOriginHeaders(),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCrossSiteAccountMutationIsRejectedBeforeItChangesLifecycleState(): void
    {
        [$organisation, $administrator] = $this->administratorScope();
        $target = $this->professional('cross-site-target');
        $this->entityManager->persist($target);
        $this->entityManager->persist(new OrganisationMembership(Uuid::v7(), $target, $organisation, ProfessionalRole::Triage, new DateTimeImmutable()));
        $this->entityManager->flush();
        $this->client->loginUser($administrator);

        $this->client->jsonRequest(
            'PATCH',
            '/api/v1/professional/organisations/'.$organisation->id()->toRfc4122().'/accounts/'.$target->id()->toRfc4122().'/status',
            ['action' => 'suspend'],
            ['HTTP_ORIGIN' => 'https://attacker.example', 'HTTP_SEC_FETCH_SITE' => 'cross-site'],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->entityManager->clear();
        $persisted = $this->entityManager->find(Professional::class, $target->id());
        self::assertInstanceOf(Professional::class, $persisted);
        self::assertSame(ProfessionalAccountStatus::Active, $persisted->accountStatus());
    }

    public function testCredentialAcceptanceIsRateLimitedWithoutAnEmailLookup(): void
    {
        for ($attempt = 0; $attempt < 5; ++$attempt) {
            $this->client->jsonRequest(
                'POST',
                '/api/v1/professional/account-credentials/accept',
                ['secret' => str_repeat('b', 64), 'password' => 'fictional secure password'],
                $this->sameOriginHeaders(),
            );
            self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        }

        $this->client->jsonRequest(
            'POST',
            '/api/v1/professional/account-credentials/accept',
            ['secret' => str_repeat('b', 64), 'password' => 'fictional secure password'],
            $this->sameOriginHeaders(),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
    }

    /** @return array{Organisation, Professional} */
    private function administratorScope(): array
    {
        $organisation = new Organisation(Uuid::v7(), 'Fictional School', PublicReportingIdentifier::generate());
        $administrator = $this->professional('administrator');
        $this->entityManager->persist($organisation);
        $this->entityManager->persist($administrator);
        $this->entityManager->persist(new OrganisationMembership(Uuid::v7(), $administrator, $organisation, ProfessionalRole::Administrator, new DateTimeImmutable()));
        $this->entityManager->flush();

        return [$organisation, $administrator];
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
        return [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_SEC_FETCH_SITE' => 'same-origin',
        ];
    }
}
