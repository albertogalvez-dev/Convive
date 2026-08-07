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

final class AddReportFollowUpEntryControllerTest extends WebTestCase
{
    private const ENDPOINT = '/api/v1/reporter/report/follow-up-entries';

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

    public function testItAppendsAFollowUpEntryToTheOwnedReport(): void
    {
        $creationResult = $this->persistReport();
        $capability = $this->issueGrant($creationResult->report);

        $this->request($capability, 'There is a new witness.');

        self::assertResponseStatusCodeSame(201);
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
            ['authorType', 'content', 'createdAt'],
            array_keys($payload),
        );
        self::assertSame('reporter', $payload['authorType']);
        self::assertSame('There is a new witness.', $payload['content']);
        self::assertIsString($payload['createdAt']);

        $this->entityManager->clear();
        $persistedEntries = $this->entityManager
            ->getRepository(ReportFollowUpEntry::class)
            ->findAll();

        self::assertCount(1, $persistedEntries);
    }

    public function testTheOriginalSubmissionRemainsUnchanged(): void
    {
        $creationResult = $this->persistReport(
            'The original description.',
        );
        $capability = $this->issueGrant($creationResult->report);

        $this->request($capability, 'A follow-up note.');
        self::assertResponseStatusCodeSame(201);

        $this->entityManager->clear();
        $persistedReport = $this->entityManager
            ->getRepository(Report::class)
            ->find($creationResult->report->id());

        self::assertSame(
            'The original description.',
            $persistedReport->situationDescription()->toString(),
        );
    }

    public function testItRejectsEmptyContent(): void
    {
        $creationResult = $this->persistReport();
        $capability = $this->issueGrant($creationResult->report);

        $this->request($capability, '   ');

        $this->assertProblemDetails(
            422,
            'urn:convive:problem:invalid-follow-up-entry',
            'Invalid follow-up entry',
        );
    }

    public function testItRejectsContentLongerThanTheMaximum(): void
    {
        $creationResult = $this->persistReport();
        $capability = $this->issueGrant($creationResult->report);

        $this->request(
            $capability,
            str_repeat('a', FollowUpEntryContent::MAX_LENGTH + 1),
        );

        $this->assertProblemDetails(
            422,
            'urn:convive:problem:invalid-follow-up-entry',
            'Invalid follow-up entry',
        );
    }

    public function testItDeniesAccessWithoutACookie(): void
    {
        $this->client->jsonRequest(
            'POST',
            self::ENDPOINT,
            ['content' => 'Attempted without a capability.'],
            server: ['HTTP_ACCEPT' => 'application/problem+json'],
        );

        $this->assertProblemDetails(
            401,
            'urn:convive:problem:report-access-capability-denied',
            'Report access capability denied',
        );
    }

    public function testItDeniesAppendingWithAnExpiredCapability(): void
    {
        $creationResult = $this->persistReport();
        $capability = ReportAccessCapability::generate();
        $grant = ReportAccessGrant::issue(
            $creationResult->report,
            $capability,
            new DateTimeImmutable('-3 hours'),
        );
        $this->entityManager->persist($grant);
        $this->entityManager->flush();

        $this->request($capability->reveal(), 'Attempted after expiry.');

        $this->assertProblemDetails(
            401,
            'urn:convive:problem:report-access-capability-denied',
            'Report access capability denied',
        );

        $this->entityManager->clear();
        $entries = $this->entityManager
            ->getRepository(ReportFollowUpEntry::class)
            ->findAll();

        self::assertSame([], $entries);
    }

    public function testItDeniesAppendingWithARevokedCapability(): void
    {
        $creationResult = $this->persistReport();
        $capability = ReportAccessCapability::generate();
        $grant = ReportAccessGrant::issue(
            $creationResult->report,
            $capability,
            new DateTimeImmutable(),
        );
        $grant->revokeAt(new DateTimeImmutable());
        $this->entityManager->persist($grant);
        $this->entityManager->flush();

        $this->request($capability->reveal(), 'Attempted after revocation.');

        $this->assertProblemDetails(
            401,
            'urn:convive:problem:report-access-capability-denied',
            'Report access capability denied',
        );
    }

    public function testAppendingToOneReportNeverAffectsAnother(): void
    {
        $ownReport = $this->persistReport('Own report.');
        $otherReport = $this->persistReport("Someone else's report.");
        $otherCapability = $this->issueGrant($otherReport->report);

        // A capability only ever resolves the single report it was
        // issued for (guaranteed by ReportAccessGuard); this proves an
        // entry appended through it lands on that report and never
        // spills over to an unrelated one.
        $this->request($otherCapability, 'Appended to the other report.');

        self::assertResponseStatusCodeSame(201);

        $this->entityManager->clear();
        $ownReportEntries = $this->entityManager
            ->getRepository(ReportFollowUpEntry::class)
            ->findBy(['report' => $ownReport->report->id()]);

        self::assertSame([], $ownReportEntries);
    }

    private function request(string $capability, string $content): void
    {
        $this->client->getCookieJar()->set(
            new BrowserKitCookie('report_access', $capability),
        );

        $this->client->jsonRequest(
            'POST',
            self::ENDPOINT,
            ['content' => $content],
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

    private function persistReport(
        string $situationDescription = 'A situation has been observed.',
    ): ReportCreationResult {
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
