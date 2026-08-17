<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Presentation\Http;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Domain\FollowUpEntryContent;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAccessCapability;
use App\Reporting\Domain\ReportAccessGrant;
use App\Reporting\Domain\ReportCreationResult;
use App\Reporting\Domain\ReportFollowUpEntry;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;
use Symfony\Component\Uid\Uuid;

final class GetReportFollowUpStateControllerTest extends WebTestCase
{
    private const ENDPOINT = '/api/v1/reporter/report';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->client->setServerParameter(
            'REMOTE_ADDR',
            $this->uniqueTestClientIp(),
        );
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

    public function testItReturnsTheStateOfTheReportOwnedByTheCapability(): void
    {
        $creationResult = $this->persistReport(
            'A student is being excluded repeatedly during break time.',
        );
        $capability = $this->issueGrant($creationResult->report);

        $this->request($capability);

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertStringContainsString(
            'no-store',
            (string) $this->client
                ->getResponse()
                ->headers
                ->get('cache-control'),
        );

        $payload = $this->responsePayload();

        self::assertSame(
            [
                'publicReference',
                'situationDescription',
                'situationContext',
                'reporterTaxonomy',
                'status',
                'progressStage',
                'createdAt',
                'followUpEntries',
            ],
            array_keys($payload),
        );
        // A freshly submitted report has had nothing happen to it yet, and
        // the honest stage is the one that says exactly that.
        self::assertSame('received', $payload['progressStage']);
        self::assertSame(
            $creationResult->report->publicReference(),
            $payload['publicReference'],
        );
        self::assertSame(
            'A student is being excluded repeatedly during break time.',
            $payload['situationDescription'],
        );
        self::assertSame('in_person', $payload['situationContext']);
        self::assertSame('andalucia-v1', $payload['reporterTaxonomy']['version']);
        self::assertSame('unknown', $payload['reporterTaxonomy']['recurrence']);
        self::assertSame('unknown', $payload['reporterTaxonomy']['attentionCue']);
        self::assertSame('received', $payload['status']);
        self::assertIsString($payload['createdAt']);
        self::assertSame([], $payload['followUpEntries']);
    }

    public function testItIncludesFollowUpEntriesOrderedOldestFirst(): void
    {
        $creationResult = $this->persistReport(
            'A situation has been observed during break time.',
        );
        $capability = $this->issueGrant($creationResult->report);

        $this->entityManager->persist(
            ReportFollowUpEntry::addedByProfessional(
                $creationResult->report,
                Uuid::fromString('0192a5c0-2222-7000-8000-000000000002'),
                FollowUpEntryContent::fromString('Second entry.'),
                new DateTimeImmutable('2026-08-07T10:05:00+00:00'),
            ),
        );
        $this->entityManager->persist(
            ReportFollowUpEntry::addedByReporter(
                $creationResult->report,
                FollowUpEntryContent::fromString('First entry.'),
                new DateTimeImmutable('2026-08-07T10:00:00+00:00'),
            ),
        );
        $this->entityManager->flush();

        $this->request($capability);

        self::assertResponseStatusCodeSame(200);
        $payload = $this->responsePayload();

        self::assertCount(2, $payload['followUpEntries']);
        self::assertSame(
            'First entry.',
            $payload['followUpEntries'][0]['content'],
        );
        self::assertSame(
            'reporter',
            $payload['followUpEntries'][0]['authorType'],
        );
        self::assertSame(
            'Second entry.',
            $payload['followUpEntries'][1]['content'],
        );
        self::assertSame(
            'professional',
            $payload['followUpEntries'][1]['authorType'],
        );
        self::assertArrayNotHasKey(
            'professionalAuthorId',
            $payload['followUpEntries'][1],
        );
    }

    public function testItDeniesAccessWithoutACookie(): void
    {
        $this->requestWithoutCookie();

        $this->assertProblemDetails(
            401,
            'urn:convive:problem:report-access-capability-denied',
            'Report access capability denied',
        );
    }

    public function testItNeverReturnsAnotherReportsData(): void
    {
        $ownReport = $this->persistReport('My own report about a hallway incident.');
        $otherReport = $this->persistReport("Someone else's confidential report.");

        // A capability issued for otherReport must never resolve ownReport
        // or leak otherReport's content through this endpoint.
        $foreignCapability = $this->issueGrant($otherReport->report);

        $this->request($foreignCapability);

        self::assertResponseStatusCodeSame(200);
        $payload = $this->responsePayload();

        self::assertSame(
            $otherReport->report->publicReference(),
            $payload['publicReference'],
        );
        self::assertNotSame(
            $ownReport->report->publicReference(),
            $payload['publicReference'],
        );
        self::assertStringNotContainsString(
            'My own report',
            (string) $this->client->getResponse()->getContent(),
        );
    }

    public function testItDeniesAccessForAnExpiredGrant(): void
    {
        $creationResult = $this->persistReport(
            'A situation has been observed during break time.',
        );
        $capability = ReportAccessCapability::generate();
        $grant = ReportAccessGrant::issue(
            $creationResult->report,
            $capability,
            new DateTimeImmutable('-3 hours'),
        );
        $this->entityManager->persist($grant);
        $this->entityManager->flush();

        $this->request($capability->reveal());

        $this->assertProblemDetails(
            401,
            'urn:convive:problem:report-access-capability-denied',
            'Report access capability denied',
        );
    }

    public function testItDeniesAccessForARevokedGrant(): void
    {
        $creationResult = $this->persistReport(
            'A situation has been observed near the entrance.',
        );
        $capability = ReportAccessCapability::generate();
        $grant = ReportAccessGrant::issue(
            $creationResult->report,
            $capability,
            new DateTimeImmutable(),
        );
        $grant->revokeAt(new DateTimeImmutable());
        $this->entityManager->persist($grant);
        $this->entityManager->flush();

        $this->request($capability->reveal());

        $this->assertProblemDetails(
            401,
            'urn:convive:problem:report-access-capability-denied',
            'Report access capability denied',
        );
    }

    public function testItDeniesAccessForAMalformedCookieValue(): void
    {
        $this->request('too-short');

        $this->assertProblemDetails(
            401,
            'urn:convive:problem:report-access-capability-denied',
            'Report access capability denied',
        );
    }

    public function testItBoundsTheReturnedHistoryAndOrdersTiesById(): void
    {
        $creationResult = $this->persistReport('A bounded history report.');
        $capability = $this->issueGrant($creationResult->report);
        $createdAt = new DateTimeImmutable('2026-08-07T10:00:00+00:00');
        $entriesById = [];

        for ($index = 0; $index < 101; ++$index) {
            $entry = ReportFollowUpEntry::addedByReporter(
                $creationResult->report,
                FollowUpEntryContent::fromString(sprintf('Tied entry %03d.', $index)),
                $createdAt,
            );
            $entriesById[$entry->id()->toRfc4122()] = $entry->content()->toString();
            $this->entityManager->persist($entry);
        }
        $this->entityManager->flush();
        ksort($entriesById);

        $this->request($capability);

        self::assertResponseStatusCodeSame(200);
        $returnedContent = array_column(
            $this->responsePayload()['followUpEntries'],
            'content',
        );
        self::assertCount(100, $returnedContent);
        self::assertSame(
            array_slice(array_values($entriesById), 0, 100),
            $returnedContent,
        );
    }

    public function testItRateLimitsReadsPerCapabilityAndIp(): void
    {
        $creationResult = $this->persistReport('A rate-limited report.');
        $capability = $this->issueGrant($creationResult->report);

        for ($attempt = 0; $attempt < 60; ++$attempt) {
            $this->request($capability);
            self::assertResponseStatusCodeSame(
                200,
                sprintf('Read attempt %d should be accepted.', $attempt + 1),
            );
        }

        $this->request($capability);

        $payload = $this->assertProblemDetails(
            429,
            'urn:convive:problem:rate-limited',
            'Too many requests',
        );
        self::assertSame('Too many requests. Try again later.', $payload['detail']);
    }

    private function request(string $capability): void
    {
        $this->client->getCookieJar()->set(
            new BrowserKitCookie('report_access', $capability),
        );

        $this->client->request(
            'GET',
            self::ENDPOINT,
            server: ['HTTP_ACCEPT' => 'application/problem+json'],
        );
    }

    private function requestWithoutCookie(): void
    {
        $this->client->request(
            'GET',
            self::ENDPOINT,
            server: ['HTTP_ACCEPT' => 'application/problem+json'],
        );
    }

    private function issueGrant(Report $report): string
    {
        $capability = ReportAccessCapability::generate();
        $grant = ReportAccessGrant::issue(
            $report,
            $capability,
            new DateTimeImmutable(),
        );
        $this->entityManager->persist($grant);
        $this->entityManager->flush();

        return $capability->reveal();
    }

    private function persistReport(string $situationDescription): ReportCreationResult
    {
        $organisation = new Organisation(
            Uuid::v7(),
            'IES Horizonte Público',
            PublicReportingIdentifier::generate(),
        );
        $this->entityManager->persist($organisation);

        $creationResult = Report::create(
            $organisation,
            SituationDescription::fromString($situationDescription),
            SituationContext::InPerson,
        );
        $this->entityManager->persist($creationResult->report);
        $this->entityManager->flush();

        return $creationResult;
    }

    private function uniqueTestClientIp(): string
    {
        return sprintf(
            '192.0.%d.%d',
            random_int(0, 255),
            random_int(0, 255),
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
