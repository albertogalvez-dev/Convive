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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class ProfessionalReportControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->client->disableReboot();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
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
        self::assertContains(
            $firstPage['items'][0]['situationPreview'],
            [
                'First authorised fictional report.',
                'Second authorised fictional report.',
                'Third authorised fictional report.',
            ],
        );

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
        self::assertIsString($body);
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
        $detailResponse = $this->client->getResponse()->getContent();
        self::assertIsString($detailResponse);
        self::assertStringNotContainsString(
            $created['secret'],
            $detailResponse,
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
        self::assertIsString($foreignResponse);

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
            $this->entityManager->getConnection()->fetchOne(
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
        self::assertSame(
            'unknown',
            $this->responsePayload()['review']['professionalTaxonomy']['concernCategory'],
        );
        $storedReview = $this->entityManager->getConnection()->fetchAssociative(
            'SELECT status, review_reason, reviewed_by_professional_id, reviewed_at, professional_concern_category, version '
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
        self::assertSame('unknown', $storedReview['professional_concern_category']);
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

    public function testProfessionalResponsesRemainAvailableBeforeAndAfterInitialReview(): void
    {
        $organisation = $this->createOrganisation('33A', 'Response School');
        $professional = $this->createProfessional('response', $organisation, ProfessionalRole::Triage);
        $created = $this->createReport($organisation, 'Response-ready fictional report.');
        $this->client->loginUser($professional);
        $endpoint = '/api/v1/professional/reports/'
            .$created['report']->id()->toRfc4122().'/responses';

        $this->client->jsonRequest(
            'POST',
            $endpoint,
            ['content' => 'We have received your information and are reviewing it.'],
            $this->sameOriginHeaders(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $firstResponse = $this->responsePayload();
        self::assertSame('professional', $firstResponse['authorType']);
        self::assertArrayNotHasKey('professionalAuthorId', $firstResponse);

        $this->client->jsonRequest(
            'POST',
            '/api/v1/professional/reports/'.$created['report']->id()->toRfc4122().'/reviews',
            ['reason' => 'Initial fictional safeguarding assessment completed.'],
            $this->sameOriginHeaders(),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->client->jsonRequest(
            'POST',
            $endpoint,
            ['content' => 'The initial review is complete and the conversation remains open.'],
            $this->sameOriginHeaders(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertSame(
            2,
            (int) $this->entityManager->getConnection()->fetchOne(
                "SELECT COUNT(*) FROM report_follow_up_entries
                 WHERE report_id = ? AND author_type = 'professional'
                 AND professional_author_id = ?",
                [$created['report']->id()->toRfc4122(), $professional->id()->toRfc4122()],
            ),
        );
    }

    public function testTriageRequiresReviewAndCreatesOneIdempotentCaseLink(): void
    {
        $organisation = $this->createOrganisation('43E', 'Triage Transition School');
        $professional = $this->createProfessional('triage-transition', $organisation, ProfessionalRole::Triage);
        $created = $this->createReport($organisation, 'A fictional report awaiting explicit triage.');
        $endpoint = '/api/v1/professional/reports/'
            .$created['report']->id()->toRfc4122().'/triage-decisions';
        $originalDescription = $created['report']->situationDescription()->toString();
        $this->client->loginUser($professional);

        $this->client->jsonRequest(
            'POST',
            $endpoint,
            ['outcome' => 'keep', 'reason' => 'Further fictional assessment is still required.'],
            $this->sameOriginHeaders(),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $this->client->jsonRequest(
            'POST',
            '/api/v1/professional/reports/'.$created['report']->id()->toRfc4122().'/reviews',
            ['reason' => 'Initial fictional safeguarding assessment completed.'],
            $this->sameOriginHeaders(),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->client->jsonRequest(
            'POST',
            $endpoint,
            ['outcome' => 'keep', 'reason' => 'Further fictional assessment is still required.'],
            $this->sameOriginHeaders(),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $keep = $this->responsePayload()['decision'];
        self::assertSame('keep', $keep['outcome']);
        self::assertNull($keep['caseId']);
        self::assertSame($professional->id()->toRfc4122(), $keep['decidedBy']['id']);

        $linkPayload = [
            'outcome' => 'link_to_case',
            'reason' => 'The fictional assessment requires a managed safeguarding case.',
        ];
        $this->client->jsonRequest('POST', $endpoint, $linkPayload, $this->sameOriginHeaders());
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $linked = $this->responsePayload()['decision'];
        self::assertSame('link_to_case', $linked['outcome']);
        self::assertIsString($linked['caseId']);

        $this->client->jsonRequest('POST', $endpoint, $linkPayload, $this->sameOriginHeaders());
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $retried = $this->responsePayload()['decision'];
        self::assertSame($linked['id'], $retried['id']);
        self::assertSame($linked['caseId'], $retried['caseId']);
        self::assertSame(1, (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM managed_cases WHERE organisation_id = ?',
            [$organisation->id()->toRfc4122()],
        ));
        $caseState = $this->entityManager->getConnection()->fetchNumeric(
            'SELECT status, modality FROM managed_cases WHERE id = ?',
            [$linked['caseId']],
        );
        self::assertNotFalse($caseState);
        self::assertSame(['assessment', 'in_person'], $caseState);

        $leadAssignment = $this->entityManager->getConnection()->fetchNumeric(
            'SELECT role, professional_id FROM case_assignments WHERE case_id = ?',
            [$linked['caseId']],
        );
        self::assertNotFalse($leadAssignment);
        self::assertSame(
            ['lead', $professional->id()->toRfc4122()],
            $leadAssignment,
        );
        self::assertSame([
            ['action' => 'case_created', 'target' => 'case'],
            ['action' => 'report_linked', 'target' => 'triage_decision'],
            ['action' => 'assignment_created', 'target' => 'assignment'],
        ], $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT action, target FROM case_audit_events WHERE case_id = ? ORDER BY occurred_at, id',
            [$linked['caseId']],
        ));
        self::assertSame(
            2,
            (int) $this->entityManager->getConnection()->fetchOne(
                'SELECT COUNT(*) FROM report_triage_decisions WHERE report_id = ?',
                [$created['report']->id()->toRfc4122()],
            ),
        );
        self::assertSame(
            $originalDescription,
            $this->entityManager->getConnection()->fetchOne('SELECT situation_description FROM reports WHERE id = ?', [
                $created['report']->id()->toRfc4122(),
            ]),
        );

        $this->client->request('GET', '/api/v1/professional/reports/'.$created['report']->id()->toRfc4122());
        self::assertResponseIsSuccessful();
        $history = $this->responsePayload()['triageDecisions'];
        self::assertSame(['keep', 'link_to_case'], array_column($history, 'outcome'));
        self::assertSame($linked['caseId'], $history[1]['caseId']);
    }

    public function testTerminalTriageCannotBeReplacedAndInvalidRequestsAreSafe(): void
    {
        $organisation = $this->createOrganisation('43F', 'Terminal Triage School');
        $professional = $this->createProfessional('terminal-triage', $organisation, ProfessionalRole::Triage);
        $created = $this->createReport($organisation, 'A fictional report that will be dismissed but retained.');
        $endpoint = '/api/v1/professional/reports/'
            .$created['report']->id()->toRfc4122().'/triage-decisions';
        $this->client->loginUser($professional);
        $this->client->jsonRequest(
            'POST',
            '/api/v1/professional/reports/'.$created['report']->id()->toRfc4122().'/reviews',
            ['reason' => 'Initial fictional safeguarding assessment completed.'],
            $this->sameOriginHeaders(),
        );

        $this->client->jsonRequest(
            'POST',
            $endpoint,
            ['outcome' => 'dismiss', 'reason' => 'The documented fictional facts do not require a managed case.'],
            $this->sameOriginHeaders(),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->client->jsonRequest(
            'POST',
            $endpoint,
            ['outcome' => 'keep', 'reason' => 'This must not replace the terminal fictional decision.'],
            $this->sameOriginHeaders(),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertSame(
            'dismiss',
            $this->entityManager->getConnection()->fetchOne(
                'SELECT outcome FROM report_triage_decisions WHERE report_id = ?',
                [$created['report']->id()->toRfc4122()],
            ),
        );
        self::assertSame(
            1,
            (int) $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM reports WHERE id = ?', [
                $created['report']->id()->toRfc4122(),
            ]),
        );

        $this->client->jsonRequest(
            'POST',
            $endpoint,
            ['outcome' => 'invented', 'reason' => 'A sufficiently long but unsupported outcome.'],
            $this->sameOriginHeaders(),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testTriagePreservesRoleOrganisationAndCsrfBoundaries(): void
    {
        $authorised = $this->createOrganisation('43G', 'Authorised Triage School');
        $foreign = $this->createOrganisation('43H', 'Foreign Triage School');
        $professional = $this->createProfessional('triage-boundary', $authorised, ProfessionalRole::Triage);
        $foreignReport = $this->createReport($foreign, 'A foreign fictional report.');
        $this->client->loginUser($professional);
        $endpoint = '/api/v1/professional/reports/'
            .$foreignReport['report']->id()->toRfc4122().'/triage-decisions';

        $this->client->jsonRequest(
            'POST',
            $endpoint,
            ['outcome' => 'dismiss', 'reason' => 'This cross-organisation action must be denied.'],
            $this->sameOriginHeaders(),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $local = $this->createReport($authorised, 'A local fictional CSRF report.');
        $this->client->jsonRequest(
            'POST',
            '/api/v1/professional/reports/'.$local['report']->id()->toRfc4122().'/triage-decisions',
            ['outcome' => 'keep', 'reason' => 'This cross-site action must be rejected safely.'],
            ['HTTP_ORIGIN' => 'https://attacker.example', 'HTTP_SEC_FETCH_SITE' => 'cross-site'],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertSame(0, (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM report_triage_decisions WHERE report_id = ?',
            [$local['report']->id()->toRfc4122()],
        ));
    }

    public function testInvalidAndAnonymousProfessionalResponsesAreRejected(): void
    {
        $organisation = $this->createOrganisation('33D', 'Response Validation School');
        $professional = $this->createProfessional(
            'response-validation',
            $organisation,
            ProfessionalRole::Triage,
        );
        $created = $this->createReport($organisation, 'Response validation report.');
        $endpoint = '/api/v1/professional/reports/'
            .$created['report']->id()->toRfc4122().'/responses';
        $this->client->loginUser($professional);

        $this->client->jsonRequest(
            'POST',
            $endpoint,
            ['content' => '   '],
            $this->sameOriginHeaders(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');

        $this->client->restart();
        $this->client->jsonRequest(
            'POST',
            $endpoint,
            ['content' => 'An anonymous request must not publish a response.'],
            $this->sameOriginHeaders(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        self::assertSame(
            0,
            (int) $this->entityManager->getConnection()->fetchOne(
                'SELECT COUNT(*) FROM report_follow_up_entries WHERE report_id = ?',
                [$created['report']->id()->toRfc4122()],
            ),
        );
    }

    public function testForeignAndCrossSiteProfessionalResponsesAreRejected(): void
    {
        $authorised = $this->createOrganisation('33B', 'Response Boundary School');
        $foreign = $this->createOrganisation('33C', 'Foreign Response School');
        $professional = $this->createProfessional('response-boundary', $authorised, ProfessionalRole::Triage);
        $foreignReport = $this->createReport($foreign, 'Foreign response report.');
        $ownReport = $this->createReport($authorised, 'CSRF response report.');
        $this->client->loginUser($professional);

        $this->client->jsonRequest(
            'POST',
            '/api/v1/professional/reports/'.$foreignReport['report']->id()->toRfc4122().'/responses',
            ['content' => 'This response must not cross organisations.'],
            $this->sameOriginHeaders(),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $this->client->jsonRequest(
            'POST',
            '/api/v1/professional/reports/'.$ownReport['report']->id()->toRfc4122().'/responses',
            ['content' => 'This cross-site response must not be published.'],
            [
                'HTTP_ORIGIN' => 'https://attacker.example',
                'HTTP_SEC_FETCH_SITE' => 'cross-site',
            ],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertSame(
            0,
            (int) $this->entityManager->getConnection()->fetchOne(
                'SELECT COUNT(*) FROM report_follow_up_entries WHERE report_id IN (?, ?)',
                [
                    $foreignReport['report']->id()->toRfc4122(),
                    $ownReport['report']->id()->toRfc4122(),
                ],
            ),
        );
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
        $responseContent = $this->client->getResponse()->getContent();
        self::assertIsString($responseContent);

        return json_decode(
            $responseContent,
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

}
