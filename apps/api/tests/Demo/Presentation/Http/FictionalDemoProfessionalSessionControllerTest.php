<?php

declare(strict_types=1);

namespace App\Tests\Demo\Presentation\Http;

use App\Demo\Domain\FictionalDemoDataset;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class FictionalDemoProfessionalSessionControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private Connection $connection;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        $this->connection = $entityManager->getConnection();
        $this->deleteOwnProfessional();
    }

    protected function tearDown(): void
    {
        $this->deleteOwnProfessional();
        $this->entityManager->clear();

        parent::tearDown();
    }

    public function testItCreatesTheReservedTriageSessionAndRejectsProfessionalWrites(): void
    {
        $this->createReservedTriageProfessional();

        $this->client->jsonRequest(
            'POST',
            '/api/v1/demo/professional-session',
            ['role' => 'triage'],
            $this->sameOriginHeaders(),
        );

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'professional' => [
                    'id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                    'name' => 'Lucía Demo',
                    'email' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_EMAIL,
                ],
                'demonstrationRole' => 'triage',
            ], JSON_THROW_ON_ERROR),
            (string) $this->client->getResponse()->getContent(),
        );

        $this->client->request('GET', '/api/v1/professional/session');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('"demonstrationRole":"triage"', (string) $this->client->getResponse()->getContent());

        $this->client->jsonRequest(
            'POST',
            '/api/v1/professional/reports/019fe900-0000-7000-8000-000000000073/responses',
            ['content' => 'This must never reach the application controller.'],
            $this->sameOriginHeaders(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testItRejectsUnknownDemonstrationRoles(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/v1/demo/professional-session',
            ['role' => 'observer'],
            $this->sameOriginHeaders(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @return array<string, string> */
    private function sameOriginHeaders(): array
    {
        return [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_SEC_FETCH_SITE' => 'same-origin',
        ];
    }

    private function createReservedTriageProfessional(): void
    {
        $professional = new Professional(
            Uuid::fromString(FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID),
            'Lucía Demo',
            ProfessionalEmail::fromString(FictionalDemoDataset::TRIAGE_PROFESSIONAL_EMAIL),
            new DateTimeImmutable(),
        );
        $this->entityManager->persist($professional);
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function deleteOwnProfessional(): void
    {
        $this->connection->executeStatement(<<<'SQL'
DELETE FROM professional_sessions
WHERE position(convert_to('019fe900-0000-7000-8000-000000000071', 'UTF8') in sess_data) > 0
SQL);
        $this->connection->executeStatement(
            'DELETE FROM professionals WHERE id = :id AND email = :email',
            [
                'id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                'email' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_EMAIL,
            ],
        );
    }
}
