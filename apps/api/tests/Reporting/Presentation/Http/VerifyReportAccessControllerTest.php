<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Presentation\Http;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAccessGrant;
use App\Reporting\Domain\ReportCreationResult;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Uid\Uuid;

final class VerifyReportAccessControllerTest extends WebTestCase
{
    private const ENDPOINT = '/api/v1/public/report-access-grants';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $entityManager = self::getContainer()->get(
            EntityManagerInterface::class,
        );
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

    public function testItIssuesACapabilityCookieForAValidSecret(): void
    {
        $creationResult = $this->persistReport();

        $this->request(
            $creationResult->plainAccessSecret,
            remoteAddr: $this->uniqueTestClientIp(),
        );

        self::assertResponseStatusCodeSame(204);
        self::assertSame('', (string) $this->client->getResponse()->getContent());
        self::assertStringContainsString(
            'no-store',
            (string) $this->client
                ->getResponse()
                ->headers
                ->get('cache-control'),
        );

        $cookie = $this->findCookie('report_access');

        self::assertNotNull($cookie);
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame('lax', $cookie->getSameSite());
        self::assertFalse($cookie->isSecure());
        self::assertSame('/', $cookie->getPath());
        self::assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            (string) $cookie->getValue(),
        );

        $grant = $this->entityManager
            ->getRepository(ReportAccessGrant::class)
            ->findOneBy(['report' => $creationResult->report]);

        self::assertInstanceOf(ReportAccessGrant::class, $grant);
    }

    public function testItDeniesAnUnknownSecret(): void
    {
        $this->request(
            str_repeat('a', 64),
            remoteAddr: $this->uniqueTestClientIp(),
        );

        $this->assertProblemDetails(
            401,
            'urn:convive:problem:report-access-denied',
            'Report access denied',
        );
        self::assertNull($this->findCookie('report_access'));
    }

    public function testItDeniesAMalformedSecretWithTheSameFailure(): void
    {
        $this->request(
            'too-short',
            remoteAddr: $this->uniqueTestClientIp(),
        );

        $this->assertProblemDetails(
            401,
            'urn:convive:problem:report-access-denied',
            'Report access denied',
        );
    }

    public function testItRateLimitsRepeatedVerificationAttempts(): void
    {
        $remoteAddr = $this->uniqueTestClientIp();

        for ($attempt = 0; $attempt < 5; ++$attempt) {
            $this->request(str_repeat('a', 64), remoteAddr: $remoteAddr);
            self::assertResponseStatusCodeSame(401);
        }

        $this->request(str_repeat('a', 64), remoteAddr: $remoteAddr);

        $payload = $this->assertProblemDetails(
            429,
            'urn:convive:problem:rate-limited',
            'Too many requests',
        );
        self::assertArrayHasKey('detail', $payload);
        self::assertNotNull(
            $this->client->getResponse()->headers->get('Retry-After'),
        );
    }

    /**
     * The rate limiter is keyed by client IP and its filesystem-backed
     * storage outlives a single PHPUnit process, so a fixed address would
     * make these tests flaky across repeated local runs.
     */
    private function uniqueTestClientIp(): string
    {
        return sprintf(
            '203.0.%d.%d',
            random_int(0, 255),
            random_int(0, 255),
        );
    }

    private function request(
        string $accessSecret,
        string $remoteAddr,
    ): void {
        $this->client->jsonRequest(
            'POST',
            self::ENDPOINT,
            ['accessSecret' => $accessSecret],
            server: [
                'REMOTE_ADDR' => $remoteAddr,
                'HTTP_ACCEPT' => 'application/problem+json',
            ],
        );
    }

    private function persistReport(): ReportCreationResult
    {
        $organisation = new Organisation(
            Uuid::v7(),
            'IES Horizonte Público',
            PublicReportingIdentifier::generate(),
        );
        $this->entityManager->persist($organisation);

        $creationResult = Report::create(
            $organisation,
            SituationDescription::fromString(
                'A situation has been observed during break time.',
            ),
            SituationContext::InPerson,
        );
        $this->entityManager->persist($creationResult->report);
        $this->entityManager->flush();

        return $creationResult;
    }

    private function findCookie(string $name): ?Cookie
    {
        foreach ($this->client->getResponse()->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $name) {
                return $cookie;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function responsePayload(): array
    {
        $content = $this->client->getResponse()->getContent();

        self::assertNotFalse($content);

        $payload = json_decode(
            $content,
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertIsArray($payload);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function assertProblemDetails(
        int $status,
        string $type,
        string $title,
    ): array {
        self::assertResponseStatusCodeSame($status);
        self::assertResponseHeaderSame(
            'content-type',
            'application/problem+json',
        );

        $payload = $this->responsePayload();

        self::assertSame($type, $payload['type']);
        self::assertSame($title, $payload['title']);
        self::assertSame($status, $payload['status']);
        self::assertArrayHasKey('detail', $payload);
        self::assertArrayNotHasKey('class', $payload);
        self::assertArrayNotHasKey('trace', $payload);

        return $payload;
    }
}
