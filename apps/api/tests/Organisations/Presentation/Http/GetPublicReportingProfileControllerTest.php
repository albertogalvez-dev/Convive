<?php

declare(strict_types=1);

namespace App\Tests\Organisations\Presentation\Http;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

final class GetPublicReportingProfileControllerTest extends WebTestCase
{
    private const ORGANISATION_IDENTIFIER = 'ORG_6H3K8M5R2W9T4Q7X';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(
            EntityManagerInterface::class,
        );
        $this->entityManager->getConnection()->beginTransaction();

        $this->entityManager->persist(
            new Organisation(
                Uuid::fromString(
                    '0192a5c0-3333-7000-8000-000000000001',
                ),
                'IES Valle Sereno',
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

    public function testItReturnsThePublicReportingProfile(): void
    {
        $this->client->request(
            'GET',
            $this->endpoint(),
            server: [
                'HTTP_ACCEPT' => 'application/json',
            ],
        );

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame(
            'content-type',
            'application/json',
        );
        self::assertSame(
            [
                'name' => 'IES Valle Sereno',
            ],
            $this->responsePayload(),
        );
    }

    public function testItHidesAnUnknownReportingOrganisation(): void
    {
        $this->client->request(
            'GET',
            $this->endpoint('ORG_9R6W3M8N5K2T7Q4X'),
            server: [
                'HTTP_ACCEPT' => 'application/problem+json',
            ],
        );

        self::assertSame(
            $this->notFoundProblemDetails(),
            $this->responsePayload(),
        );
        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame(
            'content-type',
            'application/problem+json',
        );
    }

    public function testItTreatsMalformedAndUnknownIdentifiersEqually(): void
    {
        $this->client->request(
            'GET',
            $this->endpoint('not-a-valid-identifier'),
            server: [
                'HTTP_ACCEPT' => 'application/problem+json',
            ],
        );

        self::assertSame(
            $this->notFoundProblemDetails(),
            $this->responsePayload(),
        );
        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame(
            'content-type',
            'application/problem+json',
        );
    }

    private function endpoint(
        string $identifier = self::ORGANISATION_IDENTIFIER,
    ): string {
        return sprintf(
            '/api/v1/public/organisations/%s',
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
     * @return array<string, int|string>
     */
    private function notFoundProblemDetails(): array
    {
        return [
            'type' =>
                'urn:convive:problem:reporting-organisation-not-found',
            'title' => 'Reporting organisation not found',
            'status' => 404,
            'detail' =>
                'The requested reporting organisation was not found.',
        ];
    }
}
