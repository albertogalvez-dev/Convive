<?php

declare(strict_types=1);

namespace App\Tests\Professionals\Presentation\Http;

use App\Cases\Domain\CaseAssignment;
use App\Cases\Domain\CaseAssignmentRole;
use App\Cases\Domain\CaseModality;
use App\Cases\Domain\CaseProtocolStage;
use App\Cases\Domain\CaseStatus;
use App\Cases\Domain\CaseTask;
use App\Cases\Domain\CaseTaskKind;
use App\Cases\Domain\ManagedCase;
use App\Cases\Domain\WorkflowSourceAuthority;
use App\Cases\Domain\WorkflowSourceVersion;
use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalAbsence;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRole;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class CaseContinuityControllerTest extends WebTestCase
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

    public function testAnAbsentResponsibleRaisesTheCaseWithoutExposingAnyCaseContent(): void
    {
        $organisation = $this->createOrganisation();
        $administrator = $this->createProfessional('continuity-admin', $organisation, ProfessionalRole::Administrator);
        $lead = $this->createProfessional('continuity-lead', $organisation, ProfessionalRole::Triage);
        $managedCase = $this->createCase($organisation, $lead);
        $this->entityManager->persist(new ProfessionalAbsence(
            Uuid::v7(),
            $lead,
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable('+3 days'),
            'Fictional planned absence.',
            new DateTimeImmutable(),
        ));
        $this->entityManager->flush();

        $this->client->loginUser($administrator);
        $this->client->request('GET', '/api/v1/professional/organisations/'.$organisation->id()->toRfc4122().'/case-continuity');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('no-store', (string) $this->client->getResponse()->headers->get('Cache-Control'));
        $items = $this->responsePayload()['items'];
        self::assertCount(1, $items);
        self::assertSame($managedCase->id()->toRfc4122(), $items[0]['caseId']);
        self::assertSame('responsible_absent', $items[0]['reason']);
        self::assertSame($lead->id()->toRfc4122(), $items[0]['responsible']['id']);

        // The entry is operational metadata only.
        foreach (['people', 'tasks', 'communications', 'sourceReport', 'evidence', 'timeline'] as $forbidden) {
            self::assertArrayNotHasKey($forbidden, $items[0]);
        }
    }

    public function testAnOverdueTaskRaisesTheCaseEvenWithAPresentResponsible(): void
    {
        $organisation = $this->createOrganisation();
        $administrator = $this->createProfessional('overdue-admin', $organisation, ProfessionalRole::Administrator);
        $lead = $this->createProfessional('overdue-lead', $organisation, ProfessionalRole::Triage);
        $managedCase = $this->createCase($organisation, $lead, overdue: true);
        $this->entityManager->flush();

        $this->client->loginUser($administrator);
        $this->client->request('GET', '/api/v1/professional/organisations/'.$organisation->id()->toRfc4122().'/case-continuity');

        self::assertResponseIsSuccessful();
        $items = $this->responsePayload()['items'];
        self::assertCount(1, $items);
        self::assertSame($managedCase->id()->toRfc4122(), $items[0]['caseId']);
        self::assertSame('overdue_task', $items[0]['reason']);
        self::assertNotNull($items[0]['earliestOverdueAt']);
    }

    public function testACaseWithAPresentResponsibleAndNoOverdueWorkIsNotRaised(): void
    {
        $organisation = $this->createOrganisation();
        $administrator = $this->createProfessional('quiet-admin', $organisation, ProfessionalRole::Administrator);
        $lead = $this->createProfessional('quiet-lead', $organisation, ProfessionalRole::Triage);
        $this->createCase($organisation, $lead);
        $this->entityManager->flush();

        $this->client->loginUser($administrator);
        $this->client->request('GET', '/api/v1/professional/organisations/'.$organisation->id()->toRfc4122().'/case-continuity');

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->responsePayload()['items']);
    }

    public function testTheContinuityListNamesACaseTheAdministratorStillCannotOpen(): void
    {
        $organisation = $this->createOrganisation();
        $administrator = $this->createProfessional('gate-admin', $organisation, ProfessionalRole::Administrator);
        $lead = $this->createProfessional('gate-lead', $organisation, ProfessionalRole::Triage);
        $managedCase = $this->createCase($organisation, $lead, overdue: true);
        $this->entityManager->flush();

        $this->client->loginUser($administrator);
        $this->client->request('GET', '/api/v1/professional/organisations/'.$organisation->id()->toRfc4122().'/case-continuity');
        self::assertResponseIsSuccessful();
        self::assertSame($managedCase->id()->toRfc4122(), $this->responsePayload()['items'][0]['caseId']);

        // The list tells an administrator which case needs a decision. It is
        // not a way into the case: reading it grants nothing, and restoring
        // continuity means an explicit, audited assignment.
        $this->client->request('GET', '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122());
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        // Nor is the evidence or the audit trail reachable from that knowledge.
        $this->client->request('GET', '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122().'/audit-events');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testANonAdministratorReadsNothingEvenForTheirOwnOrganisation(): void
    {
        $organisation = $this->createOrganisation();
        $lead = $this->createProfessional('triage-only', $organisation, ProfessionalRole::Triage);
        $this->createCase($organisation, $lead, overdue: true);
        $this->entityManager->flush();

        $this->client->loginUser($lead);
        $this->client->request('GET', '/api/v1/professional/organisations/'.$organisation->id()->toRfc4122().'/case-continuity');

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->responsePayload()['items']);
    }

    public function testAnAdministratorOfAnotherCentreReadsNothing(): void
    {
        $organisation = $this->createOrganisation();
        $lead = $this->createProfessional('tenant-lead', $organisation, ProfessionalRole::Triage);
        $this->createCase($organisation, $lead, overdue: true);
        $otherOrganisation = $this->createOrganisation('47B');
        $outsider = $this->createProfessional('other-centre-admin', $otherOrganisation, ProfessionalRole::Administrator);
        $this->entityManager->flush();

        $this->client->loginUser($outsider);
        $this->client->request('GET', '/api/v1/professional/organisations/'.$organisation->id()->toRfc4122().'/case-continuity');

        // Tenant isolation: an administrator elsewhere learns nothing about
        // this centre's cases, not even that they exist.
        self::assertResponseIsSuccessful();
        self::assertSame([], $this->responsePayload()['items']);
    }

    public function testACancelledAbsenceStopsRaisingTheCase(): void
    {
        $organisation = $this->createOrganisation();
        $administrator = $this->createProfessional('cancel-admin', $organisation, ProfessionalRole::Administrator);
        $lead = $this->createProfessional('cancel-lead', $organisation, ProfessionalRole::Triage);
        $this->createCase($organisation, $lead);
        $absence = new ProfessionalAbsence(
            Uuid::v7(),
            $lead,
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable('+3 days'),
            null,
            new DateTimeImmutable(),
        );
        $this->entityManager->persist($absence);
        $this->entityManager->flush();

        $this->client->loginUser($lead);
        $this->client->request('DELETE', '/api/v1/professional/absences/'.$absence->id()->toRfc4122());
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->client->loginUser($administrator);
        $this->client->request('GET', '/api/v1/professional/organisations/'.$organisation->id()->toRfc4122().'/case-continuity');
        self::assertResponseIsSuccessful();
        self::assertSame([], $this->responsePayload()['items']);
    }

    public function testAProfessionalCannotCancelSomeoneElsesAbsence(): void
    {
        $organisation = $this->createOrganisation();
        $lead = $this->createProfessional('owner-of-absence', $organisation, ProfessionalRole::Triage);
        $other = $this->createProfessional('not-the-owner', $organisation, ProfessionalRole::Administrator);
        $absence = new ProfessionalAbsence(
            Uuid::v7(),
            $lead,
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable('+3 days'),
            null,
            new DateTimeImmutable(),
        );
        $this->entityManager->persist($absence);
        $this->entityManager->flush();

        $this->client->loginUser($other);
        $this->client->request('DELETE', '/api/v1/professional/absences/'.$absence->id()->toRfc4122());

        // Even an administrator cannot act on another professional's absence.
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->client->request('GET', '/api/v1/professional/absences');
        self::assertSame([], $this->responsePayload()['items']);
    }

    public function testAnAbsenceEndingBeforeItStartsIsRejected(): void
    {
        $organisation = $this->createOrganisation();
        $professional = $this->createProfessional('invalid-absence', $organisation, ProfessionalRole::Triage);
        $this->entityManager->flush();

        $this->client->loginUser($professional);
        $this->client->jsonRequest('POST', '/api/v1/professional/absences', [
            'startsOn' => '2026-09-10',
            'endsOn' => '2026-09-01',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRecordingAnAbsenceNeitherMovesTheCaseNorRemovesOwnAccess(): void
    {
        $organisation = $this->createOrganisation();
        $lead = $this->createProfessional('still-assigned', $organisation, ProfessionalRole::Triage);
        $managedCase = $this->createCase($organisation, $lead);
        $this->entityManager->flush();

        $this->client->loginUser($lead);
        $this->client->jsonRequest('POST', '/api/v1/professional/absences', [
            'startsOn' => (new DateTimeImmutable('-1 day'))->format('Y-m-d'),
            'endsOn' => (new DateTimeImmutable('+3 days'))->format('Y-m-d'),
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // The absent professional keeps their own case: nothing was transferred.
        $this->client->request('GET', '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122());
        self::assertResponseIsSuccessful();
        self::assertSame($managedCase->id()->toRfc4122(), $this->responsePayload()['id']);
    }

    private function createOrganisation(string $code = '46A'): Organisation
    {
        $organisation = new Organisation(
            Uuid::v7(),
            'Continuity School '.$code.' '.Uuid::v7()->toRfc4122(),
            PublicReportingIdentifier::generate(),
        );
        $this->entityManager->persist($organisation);

        return $organisation;
    }

    private function createProfessional(string $name, Organisation $organisation, ProfessionalRole $role): Professional
    {
        $professional = new Professional(
            Uuid::v7(),
            ucfirst($name).' Professional',
            ProfessionalEmail::fromString($name.'-'.Uuid::v7()->toRfc4122().'@continuity-test.example'),
            new DateTimeImmutable(),
        );
        $this->entityManager->persist($professional);
        $this->entityManager->persist(new OrganisationMembership(
            Uuid::v7(),
            $professional,
            $organisation,
            $role,
            new DateTimeImmutable(),
        ));

        return $professional;
    }

    private function createCase(Organisation $organisation, Professional $lead, bool $overdue = false): ManagedCase
    {
        $now = new DateTimeImmutable();
        // An overdue task must have been created after its case, so the case
        // itself has to predate it.
        $createdAt = $overdue ? $now->modify('-10 days') : $now;
        $managedCase = new ManagedCase(Uuid::v7(), $organisation, $lead, $createdAt, CaseModality::Mixed);
        $this->entityManager->persist($managedCase);
        $this->entityManager->persist(new CaseAssignment(
            Uuid::v7(),
            $managedCase,
            $lead,
            CaseAssignmentRole::Lead,
            $lead,
            $createdAt,
        ));

        if ($overdue) {
            $source = new WorkflowSourceVersion(
                Uuid::v7(),
                'continuity-test-'.Uuid::v7()->toRfc4122(),
                '2026.1',
                'Fictional reviewed workflow source',
                'https://example.invalid/workflow-source',
                'Andalucia',
                WorkflowSourceAuthority::Binding,
                new DateTimeImmutable('2026-01-01'),
                new DateTimeImmutable('2026-01-02'),
            );
            $this->entityManager->persist($source);
            $this->entityManager->persist(new CaseTask(
                Uuid::v7(),
                $managedCase,
                $lead,
                $source,
                CaseProtocolStage::Assessment,
                CaseTaskKind::InternalAction,
                'Fictional overdue continuity task.',
                $now->modify('-2 days'),
                $lead,
                $createdAt,
            ));
        }

        self::assertSame(CaseStatus::Assessment, $managedCase->status());

        return $managedCase;
    }

    /** @return array<string, mixed> */
    private function responsePayload(): array
    {
        $content = $this->client->getResponse()->getContent();
        self::assertIsString($content);

        return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }
}
