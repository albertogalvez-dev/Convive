<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Presentation\Http;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Domain\Report;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

final class SubmitAnonymousReportControllerTest extends WebTestCase
{
    private const ORGANISATION_IDENTIFIER = 'ORG_8N5R2W7K4M9T6Q3X';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        // The rate limiter added in #41 is keyed by client IP; its
        // filesystem-backed storage outlives a single PHPUnit process,
        // so every test method needs its own address (same reasoning
        // as VerifyReportAccessControllerTest) or this file's own
        // tests would eventually rate-limit each other.
        $this->client->setServerParameter(
            'REMOTE_ADDR',
            $this->uniqueTestClientIp(),
        );
        $this->entityManager = self::getContainer()->get(
            EntityManagerInterface::class,
        );
        $this->entityManager->getConnection()->beginTransaction();

        $this->entityManager->persist(
            new Organisation(
                Uuid::fromString(
                    '0192a5c0-2222-7000-8000-000000000001',
                ),
                'IES Horizonte Público',
                PublicReportingIdentifier::fromString(
                    self::ORGANISATION_IDENTIFIER,
                ),
            ),
        );
        $this->entityManager->flush();
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

    public function testItSubmitsAnAnonymousReport(): void
    {
        $this->client->jsonRequest(
            'POST',
            $this->endpoint(),
            [
                'situationDescription' =>
                    'A student is being excluded repeatedly.',
                'situationContext' => 'in_person',
            ],
        );

        self::assertResponseStatusCodeSame(201);
        self::assertResponseHeaderSame(
            'content-type',
            'application/json',
        );
        self::assertStringContainsString(
            'no-store',
            (string) $this->client
                ->getResponse()
                ->headers
                ->get('cache-control'),
        );

        $payload = $this->responsePayload();

        self::assertMatchesRegularExpression(
            '/^[A-F0-9]{20}$/',
            $payload['publicReference'],
        );
        self::assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $payload['accessSecret'],
        );
        self::assertSame('received', $payload['status']);
        self::assertIsString($payload['createdAt']);

        $persistedReport = $this->entityManager
            ->getRepository(Report::class)
            ->findOneBy([
                'publicReference' => $payload['publicReference'],
            ]);

        self::assertInstanceOf(Report::class, $persistedReport);
        self::assertSame(
            'A student is being excluded repeatedly.',
            $persistedReport->situationDescription()->toString(),
        );
        self::assertTrue(
            $persistedReport->verifyAccessSecret(
                $payload['accessSecret'],
            ),
        );
    }

    public function testItRejectsInvalidRequestFields(): void
    {
        $this->client->jsonRequest(
            'POST',
            $this->endpoint(),
            [
                'situationDescription' => '',
                'situationContext' => 'invalid',
            ],
        );

        $payload = $this->assertProblemDetails(
            422,
            'urn:convive:problem:request-validation-failed',
            'Request validation failed',
        );

        self::assertArrayHasKey('errors', $payload);
        self::assertSame(
            [
                'situationDescription',
                'situationContext',
            ],
            array_column($payload['errors'], 'field'),
        );
    }

    public function testItRejectsMalformedJson(): void
    {
        $this->client->request(
            'POST',
            $this->endpoint(),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/problem+json',
            ],
            content: '{"situationDescription":',
        );

        $this->assertProblemDetails(
            400,
            'urn:convive:problem:malformed-json',
            'Malformed JSON',
        );
    }

    public function testItHidesAnUnknownOrganisation(): void
    {
        $this->client->jsonRequest(
            'POST',
            $this->endpoint('ORG_9R6W3M8N5K2T7Q4X'),
            [
                'situationDescription' => 'A situation was observed.',
                'situationContext' => 'in_person',
            ],
        );

        $this->assertProblemDetails(
            404,
            'urn:convive:problem:reporting-organisation-not-found',
            'Reporting organisation not found',
        );
    }

    public function testItTreatsMalformedAndUnknownIdentifiersEqually(): void
    {
        $this->client->jsonRequest(
            'POST',
            $this->endpoint('not-a-valid-identifier'),
            [
                'situationDescription' => 'A situation was observed.',
                'situationContext' => 'in_person',
            ],
        );

        $this->assertProblemDetails(
            404,
            'urn:convive:problem:reporting-organisation-not-found',
            'Reporting organisation not found',
        );
    }

    public function testItRejectsUnsupportedMethods(): void
    {
        $this->client->request(
            'GET',
            $this->endpoint(),
            server: [
                'HTTP_ACCEPT' => 'application/problem+json',
            ],
        );

        $this->assertProblemDetails(
            405,
            'about:blank',
            'Method Not Allowed',
        );
    }

    public function testItRejectsUnsupportedContentTypes(): void
    {
        $this->client->request(
            'POST',
            $this->endpoint(),
            server: [
                'CONTENT_TYPE' => 'text/plain',
                'HTTP_ACCEPT' => 'application/problem+json',
            ],
            content: 'Not JSON',
        );

        $this->assertProblemDetails(
            415,
            'urn:convive:problem:unsupported-media-type',
            'Unsupported media type',
        );
    }

    public function testItRejectsUnicodeWhitespaceOnlyDescriptions(): void
    {
        $this->client->jsonRequest(
            'POST',
            $this->endpoint(),
            [
                'situationDescription' => "\u{00A0}\u{00A0}",
                'situationContext' => 'in_person',
            ],
        );

        $this->assertProblemDetails(
            422,
            'urn:convive:problem:invalid-report-submission',
            'Invalid report submission',
        );
    }

    public function testItKeepsUnrelatedApiNotFoundResponsesGeneric(): void
    {
        $this->client->request(
            'GET',
            '/api/v1/a-route-that-does-not-exist',
            server: [
                'HTTP_ACCEPT' => 'application/problem+json',
            ],
        );

        $this->assertProblemDetails(
            404,
            'about:blank',
            'Not Found',
        );
    }

    public function testARepeatedIdempotencyKeyReplaysTheOriginalReportWithoutTheSecret(): void
    {
        $this->client->disableReboot();

        $this->client->jsonRequest(
            'POST',
            $this->endpoint(),
            [
                'situationDescription' => 'A situation was observed.',
                'situationContext' => 'in_person',
            ],
            server: ['HTTP_IDEMPOTENCY_KEY' => 'retry-key-1'],
        );
        self::assertResponseStatusCodeSame(201);
        $original = $this->responsePayload();

        $this->client->jsonRequest(
            'POST',
            $this->endpoint(),
            [
                'situationDescription' => 'A situation was observed.',
                'situationContext' => 'in_person',
            ],
            server: ['HTTP_IDEMPOTENCY_KEY' => 'retry-key-1'],
        );

        self::assertResponseStatusCodeSame(200);
        self::assertSame(
            'true',
            $this->client->getResponse()->headers->get('Idempotency-Replayed'),
        );

        $replay = $this->responsePayload();

        self::assertSame(
            $original['publicReference'],
            $replay['publicReference'],
        );
        self::assertArrayNotHasKey('accessSecret', $replay);

        $reportCount = $this->entityManager
            ->getRepository(Report::class)
            ->count([]);

        self::assertSame(1, $reportCount);
    }

    public function testDifferentIdempotencyKeysCreateIndependentReports(): void
    {
        $this->client->disableReboot();

        $this->client->jsonRequest(
            'POST',
            $this->endpoint(),
            [
                'situationDescription' => 'First independent submission.',
                'situationContext' => 'in_person',
            ],
            server: ['HTTP_IDEMPOTENCY_KEY' => 'key-a'],
        );
        self::assertResponseStatusCodeSame(201);
        $first = $this->responsePayload();

        $this->client->jsonRequest(
            'POST',
            $this->endpoint(),
            [
                'situationDescription' => 'Second independent submission.',
                'situationContext' => 'in_person',
            ],
            server: ['HTTP_IDEMPOTENCY_KEY' => 'key-b'],
        );
        self::assertResponseStatusCodeSame(201);
        $second = $this->responsePayload();

        self::assertNotSame(
            $first['publicReference'],
            $second['publicReference'],
        );
    }

    public function testItRateLimitsRepeatedSubmissions(): void
    {
        // Multiple requests in one test: without this, the kernel
        // (and its Doctrine connection) resets between requests and
        // stops seeing setUp()'s uncommitted, transaction-isolated
        // organisation after the first request.
        $this->client->disableReboot();

        for ($attempt = 0; $attempt < 5; ++$attempt) {
            $this->client->jsonRequest(
                'POST',
                $this->endpoint(),
                [
                    'situationDescription' => 'A situation was observed.',
                    'situationContext' => 'in_person',
                ],
            );
            self::assertResponseStatusCodeSame(201);
        }

        $this->client->jsonRequest(
            'POST',
            $this->endpoint(),
            [
                'situationDescription' => 'One submission too many.',
                'situationContext' => 'in_person',
            ],
            server: ['HTTP_ACCEPT' => 'application/problem+json'],
        );

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

    private function uniqueTestClientIp(): string
    {
        return sprintf(
            '203.0.%d.%d',
            random_int(0, 255),
            random_int(0, 255),
        );
    }

    private function endpoint(
        string $identifier = self::ORGANISATION_IDENTIFIER,
    ): string {
        return sprintf(
            '/api/v1/public/organisations/%s/reports',
            $identifier,
        );
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
