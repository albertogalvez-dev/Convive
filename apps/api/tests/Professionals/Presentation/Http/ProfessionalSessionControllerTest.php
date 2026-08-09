<?php

declare(strict_types=1);

namespace App\Tests\Professionals\Presentation\Http;

use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use Symfony\Component\Uid\Uuid;

final class ProfessionalSessionControllerTest extends WebTestCase
{
    private const PASSWORD = 'correct horse battery staple';

    private EntityManagerInterface $entityManager;
    private Connection $connection;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->connection = $this->entityManager->getConnection();
        $rateLimiterCache = $container->get('cache.rate_limiter');
        self::assertInstanceOf(CacheItemPoolInterface::class, $rateLimiterCache);
        $rateLimiterCache->clear();
        $this->connection->executeStatement('DELETE FROM professional_sessions');
        $this->connection->executeStatement(
            "DELETE FROM professionals WHERE email LIKE '%@session-test.example'",
        );
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement('DELETE FROM professional_sessions');
        $this->connection->executeStatement(
            "DELETE FROM professionals WHERE email LIKE '%@session-test.example'",
        );
        $this->entityManager->clear();

        parent::tearDown();
    }

    public function testValidCredentialsCreateAndRestoreAProtectedSession(): void
    {
        $this->createProfessional('alex@session-test.example');
        $client = $this->client;

        $this->login($client, '  ALEX@SESSION-TEST.EXAMPLE  ', self::PASSWORD);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'no-store',
            (string) $client->getResponse()->headers->get('Cache-Control'),
        );
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'professional' => [
                    'id' => '0192a5c0-3333-7000-8000-000000000030',
                    'name' => 'Alex Rivera',
                    'email' => 'alex@session-test.example',
                ],
            ], JSON_THROW_ON_ERROR),
            $client->getResponse()->getContent(),
        );

        $cookie = $client->getCookieJar()->get('professional_session');
        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame('/', $cookie->getPath());
        self::assertSame('lax', $cookie->getSameSite());
        self::assertFalse($cookie->isSecure());

        $client->request('GET', '/api/v1/professional/session');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('alex@session-test.example', $client->getResponse()->getContent());
        self::assertSame(1, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM professional_sessions',
        ));
    }

    public function testInvalidAndUnknownCredentialsHaveTheSameSafeResponse(): void
    {
        $this->createProfessional('known@session-test.example');
        $client = $this->client;

        $this->login($client, 'known@session-test.example', 'wrong password');
        $knownResponse = $client->getResponse()->getContent();

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $client->restart();
        $this->login($client, 'unknown@session-test.example', 'wrong password');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        self::assertSame($knownResponse, $client->getResponse()->getContent());
        self::assertStringNotContainsString('known@', $knownResponse);
        self::assertStringNotContainsString('unknown@', $knownResponse);
    }

    public function testRepeatedLoginAttemptsReturnTheDocumentedSafeRateLimitResponse(): void
    {
        $this->createProfessional('throttled@session-test.example');
        $client = $this->client;

        for ($attempt = 1; $attempt <= 5; ++$attempt) {
            $this->login($client, 'throttled@session-test.example', 'wrong password');
            self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        }

        $this->login($client, 'throttled@session-test.example', 'wrong password');

        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
        self::assertStringContainsString(
            'no-store',
            (string) $client->getResponse()->headers->get('Cache-Control'),
        );
        self::assertStringNotContainsString(
            'throttled@session-test.example',
            $client->getResponse()->getContent(),
        );
    }

    public function testLoginRenewsAnAttackerSuppliedSessionIdentifier(): void
    {
        $this->createProfessional('fixation@session-test.example');
        $client = $this->client;
        $client->getCookieJar()->set(new Cookie('professional_session', 'attacker-controlled-id'));

        $this->login($client, 'fixation@session-test.example', self::PASSWORD);

        self::assertResponseIsSuccessful();
        self::assertNotSame('attacker-controlled-id', $this->connection->fetchOne(
            'SELECT sess_id FROM professional_sessions LIMIT 1',
        ));

        $client->request('GET', '/api/v1/professional/session');
        self::assertResponseIsSuccessful();
    }

    public function testDisabledProfessionalUsesTheGenericFailure(): void
    {
        $this->createProfessional('disabled@session-test.example', false);
        $client = $this->client;

        $this->login($client, 'disabled@session-test.example', self::PASSWORD);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        self::assertStringNotContainsString('disabled', $client->getResponse()->getContent());
    }

    public function testSecurityRevisionChangeInvalidatesAnExistingSession(): void
    {
        $this->createProfessional('revision@session-test.example');
        $client = $this->client;
        $this->login($client, 'revision@session-test.example', self::PASSWORD);
        self::assertResponseIsSuccessful();

        $this->connection->executeStatement(
            'UPDATE professionals SET security_revision = security_revision + 1 WHERE email = ?',
            ['revision@session-test.example'],
        );
        $this->entityManager->clear();

        $client->request('GET', '/api/v1/professional/session');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAnonymousCapabilityCannotBecomeAProfessionalSession(): void
    {
        $client = $this->client;
        $client->getCookieJar()->set(new Cookie('report_access', str_repeat('a', 64)));

        $client->request('GET', '/api/v1/professional/session');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
        self::assertStringContainsString(
            'no-store',
            (string) $client->getResponse()->headers->get('Cache-Control'),
        );
    }

    public function testCrossSiteLoginIsRejectedBeforeCredentialsAreProcessed(): void
    {
        $this->createProfessional('csrf@session-test.example');

        $this->client->jsonRequest(
            'POST',
            '/api/v1/professional/session',
            ['email' => 'csrf@session-test.example', 'password' => self::PASSWORD],
            [
                'HTTP_ORIGIN' => 'https://attacker.example',
                'HTTP_SEC_FETCH_SITE' => 'cross-site',
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertNull($this->client->getCookieJar()->get('professional_session'));
    }

    public function testLogoutInvalidatesTheServerSideSession(): void
    {
        $this->createProfessional('logout@session-test.example');
        $client = $this->client;
        $this->login($client, 'logout@session-test.example', self::PASSWORD);
        self::assertResponseIsSuccessful();
        $authenticatedSessionId = $this->connection->fetchOne(
            'SELECT sess_id FROM professional_sessions LIMIT 1',
        );

        $client->request('DELETE', '/api/v1/professional/session', server: $this->sameOriginHeaders());

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        self::assertSame(0, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM professional_sessions WHERE sess_id = ?',
            [$authenticatedSessionId],
        ));

        $client->request('GET', '/api/v1/professional/session');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    private function createProfessional(string $email, bool $active = true): void
    {
        $professional = new Professional(
            Uuid::fromString('0192a5c0-3333-7000-8000-000000000030'),
            'Alex Rivera',
            ProfessionalEmail::fromString($email),
            new DateTimeImmutable(),
            active: $active,
        );
        $professional->replacePasswordHash(
            (new SodiumPasswordHasher(3, 10 * 1024))->hash(self::PASSWORD),
        );
        $this->entityManager->persist($professional);
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function login(KernelBrowser $client, string $email, string $password): void
    {
        $client->jsonRequest(
            'POST',
            '/api/v1/professional/session',
            ['email' => $email, 'password' => $password],
            $this->sameOriginHeaders(),
        );
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
