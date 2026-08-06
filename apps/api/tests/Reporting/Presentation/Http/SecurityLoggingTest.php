<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Presentation\Http;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\TestHandler;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

/**
 * End-to-end proof for #41's core logging requirement: security events
 * are recorded, but a submitted situation description never reaches a
 * log record, regardless of how distinctive the text is.
 */
final class SecurityLoggingTest extends WebTestCase
{
    private const ORGANISATION_IDENTIFIER = 'ORG_5K8N3W7R2M9T4Q6X';
    private const SENSITIVE_MARKER = 'UNIQUE-SENSITIVE-MARKER-fb17c2';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private TestHandler $securityLogHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->client->disableReboot();
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
                Uuid::v7(),
                'IES Horizonte Público',
                PublicReportingIdentifier::fromString(
                    self::ORGANISATION_IDENTIFIER,
                ),
            ),
        );
        $this->entityManager->flush();

        $this->securityLogHandler = self::getContainer()->get(
            'monolog.security_test_handler',
        );
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

    public function testRateLimitExceededIsLoggedWithoutTheSubmittedText(): void
    {
        for ($attempt = 0; $attempt < 5; ++$attempt) {
            $this->submit();
        }
        $this->submit();

        self::assertResponseStatusCodeSame(429);
        self::assertTrue(
            $this->securityLogHandler->hasWarningThatContains(
                'rate_limit_exceeded',
            ),
        );
        $this->assertNoRecordContainsTheSensitiveMarker();
    }

    public function testIdempotentReplayIsLoggedWithoutTheSubmittedText(): void
    {
        $this->submit(idempotencyKey: 'log-test-key');
        self::assertResponseStatusCodeSame(201);

        $this->submit(idempotencyKey: 'log-test-key');
        self::assertResponseStatusCodeSame(200);

        self::assertTrue(
            $this->securityLogHandler->hasInfoThatContains(
                'idempotent_replay',
            ),
        );
        $this->assertNoRecordContainsTheSensitiveMarker();
    }

    private function submit(?string $idempotencyKey = null): void
    {
        $server = ['HTTP_ACCEPT' => 'application/problem+json'];

        if ($idempotencyKey !== null) {
            $server['HTTP_IDEMPOTENCY_KEY'] = $idempotencyKey;
        }

        $this->client->jsonRequest(
            'POST',
            sprintf(
                '/api/v1/public/organisations/%s/reports',
                self::ORGANISATION_IDENTIFIER,
            ),
            [
                'situationDescription' => self::SENSITIVE_MARKER,
                'situationContext' => 'in_person',
            ],
            server: $server,
        );
    }

    private function assertNoRecordContainsTheSensitiveMarker(): void
    {
        foreach ($this->securityLogHandler->getRecords() as $record) {
            self::assertStringNotContainsString(
                self::SENSITIVE_MARKER,
                (string) json_encode($record->toArray()),
            );
        }
    }

    private function uniqueTestClientIp(): string
    {
        return sprintf(
            '203.0.%d.%d',
            random_int(0, 255),
            random_int(0, 255),
        );
    }
}
