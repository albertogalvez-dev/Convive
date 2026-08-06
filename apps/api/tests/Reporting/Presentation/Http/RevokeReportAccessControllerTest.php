<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Presentation\Http;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAccessCapability;
use App\Reporting\Domain\ReportAccessGrant;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;
use Symfony\Component\Uid\Uuid;

final class RevokeReportAccessControllerTest extends WebTestCase
{
    private const ENDPOINT = '/api/v1/reporter/access-grant';

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

    public function testItRevokesTheGrantPresentedByTheCookieOnASameOriginRequest(): void
    {
        $grant = $this->persistGrant();
        $capability = $grant['capability'];

        $this->client->getCookieJar()->set(
            new BrowserKitCookie('report_access', $capability),
        );

        $this->client->request(
            'DELETE',
            self::ENDPOINT,
            server: [
                'HTTP_SEC_FETCH_SITE' => 'same-origin',
                'HTTP_ACCEPT' => 'application/problem+json',
            ],
        );

        self::assertResponseStatusCodeSame(204);

        $this->entityManager->clear();
        $persistedGrant = $this->entityManager
            ->getRepository(ReportAccessGrant::class)
            ->find($grant['id']);

        self::assertNotNull($persistedGrant->revokedAt());
    }

    public function testItIsIdempotentWhenNoCapabilityCookieIsPresent(): void
    {
        $this->client->request(
            'DELETE',
            self::ENDPOINT,
            server: [
                'HTTP_SEC_FETCH_SITE' => 'same-origin',
                'HTTP_ACCEPT' => 'application/problem+json',
            ],
        );

        self::assertResponseStatusCodeSame(204);
    }

    public function testItRejectsACrossSiteRevocationRequest(): void
    {
        $this->client->request(
            'DELETE',
            self::ENDPOINT,
            server: [
                'HTTP_SEC_FETCH_SITE' => 'cross-site',
                'HTTP_ACCEPT' => 'application/problem+json',
            ],
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testItRejectsARequestWithNoOriginSignalAtAll(): void
    {
        $this->client->request(
            'DELETE',
            self::ENDPOINT,
            server: [
                'HTTP_ACCEPT' => 'application/problem+json',
            ],
        );

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @return array{id: Uuid, capability: string}
     */
    private function persistGrant(): array
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

        $capability = ReportAccessCapability::generate();
        $grant = ReportAccessGrant::issue(
            $creationResult->report,
            $capability,
            new DateTimeImmutable(),
        );
        $this->entityManager->persist($grant);
        $this->entityManager->flush();

        return [
            'id' => $grant->id(),
            'capability' => $capability->reveal(),
        ];
    }
}
