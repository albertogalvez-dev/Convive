<?php

declare(strict_types=1);

namespace App\Tests\Professionals\Presentation\Http;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRole;
use App\Reporting\Domain\FollowUpEntryContent;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportFollowUpEntry;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class ProfessionalReportControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private Connection $connection;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->entityManager->getConnection();
        $this->cleanTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanTestData();
        $this->entityManager->clear();

        parent::tearDown();
    }

    public function testAProfessionalListsOnlyReportsFromActiveTriageMemberships(): void
    {
        $authorised = $this->createOrganisation('31A', 'Authorised School');
        $foreign = $this->createOrganisation('31B', 'Foreign School');
        $professional = $this->createProfessional('triage', $authorised, ProfessionalRole::Triage);
        $authorisedReports = [
            $this->createReport($authorised, 'First authorised fictional report.'),
            $this->createReport($authorised, 'Second authorised fictional report.'),
            $this->createReport($authorised, 'Third authorised fictional report.'),
        ];
        $foreignReport = $this->createReport($foreign, 'Foreign fictional report.');
        $this->client->loginUser($professional);

        $this->client->request('GET', '/api/v1/professional/reports?limit=2');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'no-store',
            (string) $this->client->getResponse()->headers->get('Cache-Control'),
        );
        $firstPage = $this->responsePayload();
        self::assertCount(2, $firstPage['items']);
        self::assertSame(2, $firstPage['pagination']['limit']);
        self::assertIsString($firstPage['pagination']['nextCursor']);
        self::assertNotSame('', $firstPage['pagination']['nextCursor']);

        $firstPageIds = array_column($firstPage['items'], 'id');
        self::assertNotContains($foreignReport['report']->id()->toRfc4122(), $firstPageIds);

        $this->client->request(
            'GET',
            '/api/v1/professional/reports?limit=2&cursor='.
            rawurlencode($firstPage['pagination']['nextCursor']),
        );

        self::assertResponseIsSuccessful();
        $secondPage = $this->responsePayload();
        self::assertCount(1, $secondPage['items']);
        self::assertNull($secondPage['pagination']['nextCursor']);

        $returnedIds = [...$firstPageIds, ...array_column($secondPage['items'], 'id')];
        $expectedIds = array_map(
            static fn (array $created): string => $created['report']->id()->toRfc4122(),
            $authorisedReports,
        );
        sort($returnedIds);
        sort($expectedIds);
        self::assertSame($expectedIds, $returnedIds);

        $body = $this->client->getResponse()->getContent();
        self::assertStringNotContainsString($foreignReport['secret'], $body);
        foreach ($authorisedReports as $created) {
            self::assertStringNotContainsString($created['secret'], $body);
        }
    }

    public function testDetailReturnsOriginalContentAndBoundedReporterHistory(): void
    {
        $organisation = $this->createOrganisation('31C', 'Detail School');
        $professional = $this->createProfessional('detail', $organisation, ProfessionalRole::Triage);
        $created = $this->createReport(
            $organisation,
            'A fictional student is repeatedly excluded during break time.',
        );
        $entry = ReportFollowUpEntry::addedByReporter(
            $created['report'],
            FollowUpEntryContent::fromString('The fictional situation happened again today.'),
            new DateTimeImmutable('2026-08-09T20:10:00+00:00'),
        );
        $this->entityManager->persist($entry);
        $this->entityManager->flush();
        $this->client->loginUser($professional);

        $this->client->request(
            'GET',
            '/api/v1/professional/reports/'.$created['report']->id()->toRfc4122(),
        );

        self::assertResponseIsSuccessful();
        $payload = $this->responsePayload();
        self::assertSame('new', $payload['status']);
        self::assertSame(
            'A fictional student is repeatedly excluded during break time.',
            $payload['situationDescription'],
        );
        self::assertNull($payload['review']);
        self::assertSame('reporter', $payload['followUpEntries'][0]['authorType']);
        self::assertSame(
            'The fictional situation happened again today.',
            $payload['followUpEntries'][0]['content'],
        );
        self::assertStringNotContainsString(
            $created['secret'],
            $this->client->getResponse()->getContent(),
        );
    }

    public function testForeignAndUnknownIdentifiersHaveTheSameNotFoundResponse(): void
    {
        $authorised = $this->createOrganisation('31D', 'Boundary School');
        $foreign = $this->createOrganisation('31E', 'Other Boundary School');
        $professional = $this->createProfessional('boundary', $authorised, ProfessionalRole::Triage);
        $foreignReport = $this->createReport($foreign, 'Foreign boundary report.');
        $this->client->loginUser($professional);

        $this->client->request(
            'GET',
            '/api/v1/professional/reports/'.$foreignReport['report']->id()->toRfc4122(),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $foreignResponse = $this->client->getResponse()->getContent();

        $this->client->request(
            'GET',
            '/api/v1/professional/reports/0192a5c0-9999-7000-8000-000000000031',
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertSame($foreignResponse, $this->client->getResponse()->getContent());
        self::assertStringNotContainsString(
            $foreignReport['report']->id()->toRfc4122(),
            $foreignResponse,
        );

        $this->client->jsonRequest(
            'POST',
            '/api/v1/professional/reports/'.$foreignReport['report']->id()->toRfc4122().'/reviews',
            ['reason' => 'A foreign report must never be reviewed through its identifier.'],
            $this->sameOriginHeaders(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertSame($foreignResponse, $this->client->getResponse()->getContent());
        self::assertSame(
            'received',
            $this->connection->fetchOne(
                'SELECT status FROM reports WHERE id = ?',
                [$foreignReport['report']->id()->toRfc4122()],
            ),
        );
    }

    public function testOnlyAnActiveTriageMembershipCanReadReportContent(): void
    {
        $organisation = $this->createOrganisation('31F', 'Role School');
        $report = $this->createReport($organisation, 'Role protected fictional report.');
        $administrator = $this->createProfessional(
            'administrator',
            $organisation,
            ProfessionalRole::Administrator,
        );
        $this->client->loginUser($administrator);

        $this->client->request('GET', '/api/v1/professional/reports');
        self::assertResponseIsSuccessful();
        self::assertSame([], $this->responsePayload()['items']);

        $this->client->request(
            'GET',
            '/api/v1/professional/reports/'.$report['report']->id()->toRfc4122(),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $this->client->restart();
        $revoked = $this->createProfessional('revoked', $organisation, ProfessionalRole::Triage, true);
        $this->client->loginUser($revoked);
        $this->client->request('GET', '/api/v1/professional/reports');

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->responsePayload()['items']);
    }

    public function testReviewTransitionAndStatusFiltersAreOrganisationScoped(): void
    {
        $organisation = $this->createOrganisation('31G', 'Review School');
        $professional = $this->createProfessional('review', $organisation, ProfessionalRole::Triage);
        $created = $this->createReport($organisation, 'Reviewable fictional report.');
        $this->client->loginUser($professional);

        $this->client->jsonRequest(
            'POST',
            '/api/v1/professional/reports/'.$created['report']->id()->toRfc4122().'/reviews',
            ['reason' => 'Too short'],
            $this->sameOriginHeaders(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');

        $this->client->jsonRequest(
            'POST',
            '/api/v1/professional/reports/'.$created['report']->id()->toRfc4122().'/reviews',
            ['reason' => 'Initial fictional safeguarding assessment completed.'],
            $this->sameOriginHeaders(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertSame(
            'Initial fictional safeguarding assessment completed.',
            $this->responsePayload()['review']['reason'],
        );
        $storedReview = $this->connection->fetchAssociative(
            'SELECT status, review_reason, reviewed_by_professional_id, reviewed_at, version '
            .'FROM reports WHERE id = ?',
            [$created['report']->id()->toRfc4122()],
        );
        self::assertIsArray($storedReview);
        self::assertSame('reviewed', $storedReview['status']);
        self::assertSame(
            'Initial fictional safeguarding assessment completed.',
            $storedReview['review_reason'],
        );
        self::assertSame(
            $professional->id()->toRfc4122(),
            $storedReview['reviewed_by_professional_id'],
        );
        self::assertNotNull($storedReview['reviewed_at']);
        self::assertSame(2, (int) $storedReview['version']);

        $this->client->request('GET', '/api/v1/professional/reports?status=new');
        self::assertResponseIsSuccessful();
        self::assertSame([], $this->responsePayload()['items']);

        $this->client->request('GET', '/api/v1/professional/reports?status=reviewed');
        self::assertResponseIsSuccessful();
        $reviewed = $this->responsePayload()['items'];
        self::assertCount(1, $reviewed);
        self::assertSame('reviewed', $reviewed[0]['status']);

        $this->client->jsonRequest(
            'POST',
            '/api/v1/professional/reports/'.$created['report']->id()->toRfc4122().'/reviews',
            ['reason' => 'A duplicate review must preserve the initial decision.'],
            $this->sameOriginHeaders(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
    }

    public function testCrossSiteReviewIsRejectedWithoutChangingTheReport(): void
    {
        $organisation = $this->createOrganisation('31H', 'CSRF School');
        $professional = $this->createProfessional('csrf', $organisation, ProfessionalRole::Triage);
        $created = $this->createReport($organisation, 'CSRF protected fictional report.');
        $this->client->loginUser($professional);

        $this->client->jsonRequest(
            'POST',
            '/api/v1/professional/reports/'.$created['report']->id()->toRfc4122().'/reviews',
            ['reason' => 'This cross-site review must never be recorded.'],
            [
                'HTTP_ORIGIN' => 'https://attacker.example',
                'HTTP_SEC_FETCH_SITE' => 'cross-site',
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->client->request(
            'GET',
            '/api/v1/professional/reports/'.$created['report']->id()->toRfc4122(),
        );
        self::assertResponseIsSuccessful();
        self::assertSame('new', $this->responsePayload()['status']);
    }

    public function testInvalidPaginationAndFiltersReturnSafeBadRequests(): void
    {
        $organisation = $this->createOrganisation('31I', 'Validation School');
        $professional = $this->createProfessional('validation', $organisation, ProfessionalRole::Triage);
        $this->client->loginUser($professional);

        foreach (['status=unknown', 'limit=0', 'limit=51', 'cursor=not-a-cursor'] as $query) {
            $this->client->request('GET', '/api/v1/professional/reports?'.$query);
            self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
            self::assertResponseHeaderSame('content-type', 'application/problem+json');
        }
    }

    public function testAnonymousRequestsCannotAccessTheProfessionalInbox(): void
    {
        $this->client->request('GET', '/api/v1/professional/reports');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
        self::assertStringContainsString(
            'no-store',
            (string) $this->client->getResponse()->headers->get('Cache-Control'),
        );
    }

    private function createOrganisation(string $suffix, string $name): Organisation
    {
        $identifier = 'ORG_1NB0X'.str_pad($suffix, 11, '0');
        $organisation = new Organisation(
            Uuid::v7(),
            $name,
            PublicReportingIdentifier::fromString($identifier),
        );
        $this->entityManager->persist($organisation);
        $this->entityManager->flush();

        return $organisation;
    }

    private function createProfessional(
        string $name,
        Organisation $organisation,
        ProfessionalRole $role,
        bool $revoked = false,
    ): Professional {
        $organisation = $this->entityManager->find(
            Organisation::class,
            $organisation->id(),
        ) ?? throw new \LogicException('The test organisation must be persisted.');
        $professional = new Professional(
            Uuid::v7(),
            ucfirst($name).' Professional',
            ProfessionalEmail::fromString($name.'@inbox-test.example'),
            new DateTimeImmutable(),
        );
        $membership = new OrganisationMembership(
            Uuid::v7(),
            $professional,
            $organisation,
            $role,
            new DateTimeImmutable(),
        );

        if ($revoked) {
            $membership->revokeAt(new DateTimeImmutable());
        }

        $this->entityManager->persist($professional);
        $this->entityManager->persist($membership);
        $this->entityManager->flush();

        return $professional;
    }

    /** @return array{report: Report, secret: string} */
    private function createReport(Organisation $organisation, string $description): array
    {
        $created = Report::create(
            $organisation,
            SituationDescription::fromString($description),
            SituationContext::InPerson,
        );
        $this->entityManager->persist($created->report);
        $this->entityManager->flush();

        return ['report' => $created->report, 'secret' => $created->plainAccessSecret];
    }

    /** @return array<string, mixed> */
    private function responsePayload(): array
    {
        return json_decode(
            $this->client->getResponse()->getContent(),
            true,
            flags: JSON_THROW_ON_ERROR,
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

    private function cleanTestData(): void
    {
        $this->connection->executeStatement('DELETE FROM professional_sessions');
        $this->connection->executeStatement(
            "DELETE FROM report_follow_up_entries WHERE report_id IN (
                SELECT reports.id FROM reports
                INNER JOIN organisations ON organisations.id = reports.organisation_id
                WHERE organisations.public_reporting_identifier LIKE 'ORG_1NB0X%'
            )",
        );
        $this->connection->executeStatement(
            "DELETE FROM report_access_grants WHERE report_id IN (
                SELECT reports.id FROM reports
                INNER JOIN organisations ON organisations.id = reports.organisation_id
                WHERE organisations.public_reporting_identifier LIKE 'ORG_1NB0X%'
            )",
        );
        $this->connection->executeStatement(
            "DELETE FROM reports WHERE organisation_id IN (
                SELECT id FROM organisations WHERE public_reporting_identifier LIKE 'ORG_1NB0X%'
            )",
        );
        $this->connection->executeStatement(
            "DELETE FROM organisation_memberships WHERE professional_id IN (
                SELECT id FROM professionals WHERE email LIKE '%@inbox-test.example'
            )",
        );
        $this->connection->executeStatement(
            "DELETE FROM professionals WHERE email LIKE '%@inbox-test.example'",
        );
        $this->connection->executeStatement(
            "DELETE FROM organisations WHERE public_reporting_identifier LIKE 'ORG_1NB0X%'",
        );
    }
}
