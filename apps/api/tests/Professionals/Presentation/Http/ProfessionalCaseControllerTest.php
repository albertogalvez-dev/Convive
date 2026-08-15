<?php

declare(strict_types=1);

namespace App\Tests\Professionals\Presentation\Http;

use App\Cases\Domain\CaseAssignment;
use App\Cases\Domain\CaseAssignmentRole;
use App\Cases\Domain\CaseInvolvedPerson;
use App\Cases\Domain\CaseInvolvedPersonName;
use App\Cases\Domain\CaseInvolvedPersonRole;
use App\Cases\Domain\CaseModality;
use App\Cases\Domain\CaseProtocolStage;
use App\Cases\Domain\CaseTask;
use App\Cases\Domain\CaseTaskKind;
use App\Cases\Domain\ManagedCase;
use App\Cases\Domain\WorkflowSourceAuthority;
use App\Cases\Domain\WorkflowSourceVersion;
use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRole;
use App\Professionals\Domain\ProfessionalNotification;
use App\Professionals\Domain\ProfessionalNotificationType;
use App\Reporting\Application\AttachmentStorage;
use App\Reporting\Domain\AttachmentDescription;
use App\Reporting\Domain\AttachmentMediaType;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportAttachmentRepository;
use App\Reporting\Domain\ReportTriageDecision;
use App\Reporting\Domain\ReportTriageOutcome;
use App\Reporting\Domain\ReportTriageReason;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class ProfessionalCaseControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private KernelBrowser $client;
    private AttachmentStorage $storage;
    private ReportAttachmentRepository $attachments;

    /** @var list<string> */
    private array $storedAttachmentIds = [];

    /** @var list<string> */
    private array $temporaryPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->client->setServerParameter('REMOTE_ADDR', $this->uniqueTestClientIp());
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $storage = self::getContainer()->get(AttachmentStorage::class);
        $attachments = self::getContainer()->get(ReportAttachmentRepository::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        self::assertInstanceOf(AttachmentStorage::class, $storage);
        self::assertInstanceOf(ReportAttachmentRepository::class, $attachments);
        $this->entityManager = $entityManager;
        $this->storage = $storage;
        $this->attachments = $attachments;
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            foreach ($this->storedAttachmentIds as $id) {
                $attachment = $this->entityManager->find(ReportAttachment::class, Uuid::fromString($id));
                if ($attachment instanceof ReportAttachment) {
                    $this->storage->delete($attachment);
                }
            }

            $connection = $this->entityManager->getConnection();
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $this->entityManager->clear();
        }

        foreach ($this->temporaryPaths as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    public function testAssignedProfessionalReadsOnlyTheirOperationalCaseWorkspace(): void
    {
        [$managedCase, $lead, , $attachment] = $this->createCaseWorkspace();
        $this->client->loginUser($lead);

        $this->client->request('GET', '/api/v1/professional/cases');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'no-store',
            (string) $this->client->getResponse()->headers->get('Cache-Control'),
        );
        $list = $this->responsePayload();
        self::assertCount(1, $list['items']);
        self::assertSame($managedCase->id()->toRfc4122(), $list['items'][0]['id']);
        self::assertSame('lead', $list['items'][0]['assignmentRole']);
        self::assertSame(1, $list['items'][0]['pendingTasks']);

        $this->client->request('GET', '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122());

        self::assertResponseIsSuccessful();
        $detail = $this->responsePayload();
        self::assertSame($managedCase->id()->toRfc4122(), $detail['id']);
        self::assertSame('mixed', $detail['modality']);
        self::assertTrue($detail['permissions']['manage']);
        self::assertTrue($detail['permissions']['manageAssignments']);
        self::assertTrue($detail['permissions']['export']);
        self::assertTrue($detail['permissions']['viewAudit']);
        self::assertSame('Fictional affected person', $detail['people'][0]['name']);
        self::assertSame('inspection_communication', $detail['tasks'][0]['stage']);
        self::assertSame('binding', $detail['tasks'][0]['source']['authority']);
        self::assertSame($attachment->id()->toRfc4122(), $detail['evidence'][0]['id']);
        self::assertSame('link_to_case', $detail['sourceReport']['decision']['outcome']);
        self::assertSame('A fictional triage decision opened this managed case.', $detail['sourceReport']['decision']['reason']);
        self::assertArrayHasKey('timeline', $detail);
        self::assertArrayNotHasKey('label', $detail['timeline'][0]);

        $this->client->request(
            'GET',
            '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122().'/evidence/'.$attachment->id()->toRfc4122().'/download',
        );
        self::assertResponseIsSuccessful();
        self::assertSame('application/pdf', $this->client->getResponse()->headers->get('content-type'));
        self::assertSame('%PDF-1.7\nfake case evidence\n', $this->client->getInternalResponse()->getContent());

        $this->client->request('GET', '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122().'/audit-events');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'no-store',
            (string) $this->client->getResponse()->headers->get('Cache-Control'),
        );
        $audit = $this->responsePayload();
        self::assertSame('evidence_download_authorised', $audit['items'][0]['action']);
        self::assertSame('attachment', $audit['items'][0]['target']);
        self::assertArrayNotHasKey('targetId', $audit['items'][0]);

        $this->client->request('GET', '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122().'/audit-events/export');
        self::assertResponseIsSuccessful();
        self::assertSame('text/csv; charset=utf-8', $this->client->getResponse()->headers->get('content-type'));
        $export = $this->client->getInternalResponse()->getContent();
        self::assertStringContainsString('occurred_at,action,target,actor', $export);
        self::assertStringContainsString('audit_exported', $export);
        self::assertStringNotContainsString('Fictional case evidence.', $export);

        $this->client->request('GET', '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122().'/export');
        self::assertResponseIsSuccessful();
        self::assertSame('application/pdf', $this->client->getResponse()->headers->get('content-type'));
        self::assertStringContainsString('no-store', (string) $this->client->getResponse()->headers->get('cache-control'));
        self::assertSame('noindex, noarchive', $this->client->getResponse()->headers->get('x-robots-tag'));
        $casePdf = $this->client->getResponse()->getContent();
        self::assertIsString($casePdf);
        self::assertStringStartsWith('%PDF-', $casePdf);
        self::assertStringNotContainsString('Fictional affected person', $casePdf);

        $this->client->request('GET', '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122().'/audit-events');
        self::assertResponseIsSuccessful();
        $audit = $this->responsePayload();
        self::assertSame('case_record_exported', $audit['items'][count($audit['items']) - 1]['action']);
        self::assertSame('case_record', $audit['items'][count($audit['items']) - 1]['target']);

        $this->client->request('GET', '/api/v1/professional/cases/operational-overview/export');
        self::assertResponseIsSuccessful();
        self::assertSame('application/pdf', $this->client->getResponse()->headers->get('content-type'));
        self::assertStringStartsWith('%PDF-', (string) $this->client->getResponse()->getContent());
        self::assertSame('noindex, noarchive', $this->client->getResponse()->headers->get('x-robots-tag'));
        self::assertSame(1, (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM professional_export_events WHERE professional_id = :professionalId AND kind = :kind',
            [
                'professionalId' => $lead->id()->toRfc4122(),
                'kind' => 'operational_overview',
            ],
        ));
    }

    public function testOrganisationMembershipWithoutExactCaseAssignmentCannotDiscoverTheCase(): void
    {
        [$managedCase, , $organisation, $attachment] = $this->createCaseWorkspace();
        $administrator = $this->createProfessional('case-administrator', $organisation, ProfessionalRole::Administrator);
        $this->client->loginUser($administrator);

        $this->client->request('GET', '/api/v1/professional/cases');
        self::assertResponseIsSuccessful();
        self::assertSame([], $this->responsePayload()['items']);

        foreach (['overdue', 'upcoming', 'recent'] as $view) {
            $this->client->request('GET', '/api/v1/professional/cases?view='.$view);
            self::assertResponseIsSuccessful();
            self::assertSame([], $this->responsePayload()['items']);
        }

        $this->client->request('GET', '/api/v1/professional/cases/operational-summary');
        self::assertResponseIsSuccessful();
        self::assertSame(['assigned' => 0, 'overdue' => 0, 'upcoming' => 0], $this->responsePayload());

        $this->client->request('GET', '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122());
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $denied = $this->client->getResponse()->getContent();
        self::assertIsString($denied);

        $this->client->request('GET', '/api/v1/professional/cases/0192a5c0-9999-7000-8000-000000000046');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertSame($denied, $this->client->getResponse()->getContent());
        self::assertStringNotContainsString($managedCase->id()->toRfc4122(), $denied);

        $this->client->request(
            'GET',
            '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122().'/evidence/'.$attachment->id()->toRfc4122().'/download',
        );
        $deniedEvidence = $this->client->getResponse()->getContent();
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertIsString($deniedEvidence);

        $this->client->request(
            'GET',
            '/api/v1/professional/cases/0192a5c0-9999-7000-8000-000000000046/evidence/'.$attachment->id()->toRfc4122().'/download',
        );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertSame($deniedEvidence, $this->client->getResponse()->getContent());

        $this->client->request('GET', '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122().'/audit-events');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $deniedAudit = $this->client->getResponse()->getContent();
        self::assertIsString($deniedAudit);

        $this->client->request('GET', '/api/v1/professional/cases/0192a5c0-9999-7000-8000-000000000046/audit-events');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertSame($deniedAudit, $this->client->getResponse()->getContent());
    }

    public function testLeadCanCreateAndExplicitlyResolveCaseTasks(): void
    {
        [$managedCase, $lead] = $this->createCaseWorkspace();
        $this->client->loginUser($lead);
        $detailUrl = '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122();
        $this->client->request('GET', $detailUrl);
        $detail = $this->responsePayload();
        $this->client->request('GET', $detailUrl.'/task-planning-catalogue');
        self::assertResponseIsSuccessful();
        $templates = $this->responsePayload()['items'];
        self::assertCount(3, $templates);
        self::assertSame(['binding', 'binding', 'internal'], array_column(array_column($templates, 'source'), 'authority'));
        self::assertSame(['ES-AN', 'ES-AN', 'ES-AN-GR'], array_column(array_column($templates, 'source'), 'territory'));
        $templateId = $templates[0]['id'];

        $this->client->jsonRequest('POST', $detailUrl.'/tasks', [
            'ownerId' => $lead->id()->toRfc4122(),
            'templateId' => $templateId,
            'title' => 'Record the fictional follow-up action.',
            'dueAt' => '2030-01-02T10:00:00+00:00',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $created = $this->responsePayload();
        self::assertSame('pending', $created['status']);
        self::assertSame($templates[0]['source']['title'], $created['source']['title']);

        $this->client->jsonRequest('POST', $detailUrl.'/tasks/'.$created['id'].'/not-applicable', [
            'reason' => 'The fictional action is not needed for this case.',
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame('not_applicable', $this->responsePayload()['status']);

        $this->client->request('POST', $detailUrl.'/tasks/'.$created['id'].'/complete');
        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testLeadCanAppendACommunicationRecordAndTraceableCorrection(): void
    {
        [$managedCase, $lead] = $this->createCaseWorkspace();
        $this->client->loginUser($lead);
        $url = '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122().'/communications';
        $payload = [
            'responsibleId' => $lead->id()->toRfc4122(),
            'recipient' => 'family',
            'channel' => 'telephone',
            'status' => 'recorded',
            'occurredAt' => '2030-01-02T10:00:00+00:00',
            'note' => 'Fictional family contact was recorded without delivery evidence.',
        ];
        $this->client->jsonRequest('POST', $url, $payload);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $created = $this->responsePayload();
        self::assertSame('recorded', $created['status']);
        self::assertNull($created['supersedesId']);

        $payload['note'] = 'Fictional corrected communication record.';
        $this->client->jsonRequest('POST', $url.'/'.$created['id'].'/corrections', $payload);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertSame($created['id'], $this->responsePayload()['supersedesId']);

        $this->client->request('GET', '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122());
        self::assertResponseIsSuccessful();
        self::assertCount(2, $this->responsePayload()['communications']);
    }

    public function testLeadCanCloseAndReopenCaseWithAnExplicitRecordButObserverCannot(): void
    {
        [$managedCase, $lead, $organisation] = $this->createCaseWorkspace();
        $url = '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122().'/lifecycle';
        $this->client->loginUser($lead);
        $this->client->jsonRequest('POST', $url, ['status' => 'closed', 'reason' => 'Fictional case review is complete.', 'evidence' => 'Fictional closure record reviewed.'], $this->sameOriginHeaders());
        self::assertResponseIsSuccessful();
        self::assertSame('closed', $this->responsePayload()['status']);

        $this->client->jsonRequest('POST', $url, ['status' => 'active', 'reason' => 'Fictional review needs to continue.', 'evidence' => 'Fictional continuation record reviewed.'], $this->sameOriginHeaders());
        self::assertResponseIsSuccessful();
        self::assertSame('active', $this->responsePayload()['status']);

        $managedOrganisation = $this->entityManager->getReference(Organisation::class, $organisation->id());
        $managedCase = $this->entityManager->getReference(ManagedCase::class, $managedCase->id());
        $managedLead = $this->entityManager->getReference(Professional::class, $lead->id());
        self::assertInstanceOf(Organisation::class, $managedOrganisation);
        self::assertInstanceOf(ManagedCase::class, $managedCase);
        self::assertInstanceOf(Professional::class, $managedLead);

        $observer = $this->createProfessional(
            'case-lifecycle-observer',
            $managedOrganisation,
            ProfessionalRole::Triage,
        );
        $this->entityManager->persist(new CaseAssignment(
            Uuid::v7(),
            $managedCase,
            $observer,
            CaseAssignmentRole::Observer,
            $managedLead,
            new DateTimeImmutable(),
        ));
        $this->entityManager->flush();
        $this->client->loginUser($observer);
        $this->client->jsonRequest('POST', $url, ['status' => 'closed', 'reason' => 'Fictional denied closure.', 'evidence' => 'Fictional record.'], $this->sameOriginHeaders());
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testLeadCanAddCorrectAndLogicallyRemoveAMinimisedCasePerson(): void
    {
        [$managedCase, $lead] = $this->createCaseWorkspace();
        $this->client->loginUser($lead);
        $url = '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122().'/people';

        $this->client->jsonRequest('POST', $url, ['name' => 'Fictional witness', 'role' => 'witness'], $this->sameOriginHeaders());
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $person = $this->responsePayload();
        self::assertSame('Fictional witness', $person['name']);
        self::assertSame('active', $person['state']);

        $this->client->jsonRequest('PATCH', $url.'/'.$person['id'], ['name' => 'Corrected fictional witness', 'role' => 'guardian'], $this->sameOriginHeaders());
        self::assertResponseIsSuccessful();
        self::assertSame('guardian', $this->responsePayload()['role']);

        $this->client->request('DELETE', $url.'/'.$person['id'], [], [], $this->sameOriginHeaders());
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->client->request('GET', '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122());
        self::assertResponseIsSuccessful();
        self::assertSame('removed', array_values(array_filter($this->responsePayload()['people'], static fn (array $item): bool => $item['id'] === $person['id']))[0]['state']);
    }

    public function testObserverCannotManageCasePeopleAndUnapprovedFieldsAreRejected(): void
    {
        [$managedCase, $lead, $organisation] = $this->createCaseWorkspace();
        $url = '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122().'/people';
        $this->client->loginUser($lead);
        $this->client->jsonRequest('POST', $url, ['name' => 'Fictional person', 'role' => 'affected', 'academicRecord' => 'not permitted'], $this->sameOriginHeaders());
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $observer = $this->createProfessional('case-people-observer', $organisation, ProfessionalRole::Triage);
        $this->entityManager->persist(new CaseAssignment(Uuid::v7(), $managedCase, $observer, CaseAssignmentRole::Observer, $lead, new DateTimeImmutable()));
        $this->entityManager->flush();
        $this->client->loginUser($observer);
        $this->client->jsonRequest('POST', $url, ['name' => 'Fictional person', 'role' => 'affected'], $this->sameOriginHeaders());
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testObserverCannotCreateOrResolveCaseTasks(): void
    {
        [$managedCase, $lead, $organisation] = $this->createCaseWorkspace();
        $observer = $this->createProfessional('case-observer', $organisation, ProfessionalRole::Triage);
        $assignment = new CaseAssignment(
            Uuid::v7(),
            $managedCase,
            $observer,
            CaseAssignmentRole::Observer,
            $lead,
            new DateTimeImmutable(),
        );
        $this->entityManager->persist($assignment);
        $this->entityManager->flush();
        $this->client->loginUser($observer);
        $url = '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122();
        $this->client->request('GET', $url);
        $detail = $this->responsePayload();

        $this->client->jsonRequest('POST', $url.'/tasks', [
            'ownerId' => $lead->id()->toRfc4122(),
            'templateId' => Uuid::v7()->toRfc4122(),
            'title' => 'Denied fictional action.',
            'dueAt' => '2030-01-02T10:00:00+00:00',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $this->client->jsonRequest('POST', $url.'/communications', [
            'responsibleId' => $lead->id()->toRfc4122(),
            'recipient' => 'family',
            'channel' => 'telephone',
            'status' => 'planned',
            'occurredAt' => '2030-01-02T10:00:00+00:00',
            'note' => 'Denied fictional communication record.',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $this->client->request('POST', $url.'/tasks/'.$detail['tasks'][0]['id'].'/complete');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testLeadCanHandoverBeforeRevokingTheFormerLead(): void
    {
        [$managedCase, $lead, $organisation] = $this->createCaseWorkspace();
        $nextLead = $this->createProfessional('next-case-lead', $organisation, ProfessionalRole::Triage);
        $this->client->loginUser($lead);
        $url = '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122();

        $this->client->jsonRequest('POST', $url.'/assignments/'.$this->activeAssignmentId($managedCase, $lead).'/handover', [
            'professionalId' => $nextLead->id()->toRfc4122(),
            'reason' => 'Fictional planned handover.',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $newAssignment = $this->responsePayload();
        self::assertSame('lead', $newAssignment['role']);

        $this->client->request('GET', $url);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->client->loginUser($nextLead);
        $this->client->request('GET', $url);
        self::assertResponseIsSuccessful();
        self::assertCount(1, array_filter($this->responsePayload()['assignments'], static fn (array $assignment): bool => $assignment['role'] === 'lead'));
    }

    public function testLeadCannotRevokeTheFinalLeadAndObserverCannotChangeAssignments(): void
    {
        [$managedCase, $lead, $organisation] = $this->createCaseWorkspace();
        $url = '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122();
        $this->client->loginUser($lead);
        $this->client->jsonRequest('POST', $url.'/assignments/'.$this->activeAssignmentId($managedCase, $lead).'/revoke', [
            'reason' => 'Fictional unsupported removal.',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $observer = $this->createProfessional('assignment-observer', $organisation, ProfessionalRole::Triage);
        $this->entityManager->persist(new CaseAssignment(Uuid::v7(), $managedCase, $observer, CaseAssignmentRole::Observer, $lead, new DateTimeImmutable()));
        $this->entityManager->flush();
        $this->client->loginUser($observer);
        $this->client->jsonRequest('POST', $url.'/assignments', [
            'professionalId' => $lead->id()->toRfc4122(),
            'role' => 'contributor',
            'reason' => 'Fictional denied assignment.',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testLeadCanAssignChangeAndExplicitlyRevokeAContributor(): void
    {
        [$managedCase, $lead, $organisation] = $this->createCaseWorkspace();
        $contributor = $this->createProfessional('assignment-contributor', $organisation, ProfessionalRole::Triage);
        $url = '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122();
        $this->client->loginUser($lead);

        $this->client->jsonRequest('POST', $url.'/assignments', [
            'professionalId' => $contributor->id()->toRfc4122(),
            'role' => 'contributor',
            'reason' => 'Fictional collaboration needed for follow-up.',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $assignment = $this->responsePayload();
        self::assertSame('contributor', $assignment['role']);

        $this->client->jsonRequest('POST', $url.'/assignments/'.$assignment['id'].'/role', [
            'role' => 'observer',
            'reason' => 'Fictional collaboration is now limited to consultation.',
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame('observer', $this->responsePayload()['role']);

        $this->client->loginUser($contributor);
        $this->client->request('GET', $url);
        self::assertResponseIsSuccessful();

        $this->client->loginUser($lead);
        $this->client->jsonRequest('POST', $url.'/assignments/'.$assignment['id'].'/revoke', [
            'reason' => 'Fictional collaboration is no longer required.',
        ]);
        self::assertResponseIsSuccessful();
        self::assertTrue($this->responsePayload()['revoked']);

        $this->client->loginUser($contributor);
        $this->client->request('GET', $url);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAssignmentIdentifierFromAnotherCaseCannotBeUsed(): void
    {
        [$firstCase, $lead, $organisation] = $this->createCaseWorkspace();
        $secondCase = new ManagedCase(
            Uuid::v7(),
            $organisation,
            $lead,
            new DateTimeImmutable(),
            CaseModality::Mixed,
        );
        $observer = $this->createProfessional('cross-case-observer', $organisation, ProfessionalRole::Triage);
        $secondAssignment = new CaseAssignment(
            Uuid::v7(),
            $secondCase,
            $observer,
            CaseAssignmentRole::Observer,
            $lead,
            new DateTimeImmutable(),
        );
        $this->entityManager->persist($secondCase);
        $this->entityManager->persist($secondAssignment);
        $this->entityManager->flush();
        $this->client->loginUser($lead);

        $this->client->jsonRequest('POST', '/api/v1/professional/cases/'.$firstCase->id()->toRfc4122().'/assignments/'.$secondAssignment->id()->toRfc4122().'/revoke', [
            'reason' => 'Fictional cross-case attempt.',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testContributorCanReadTheCaseButCannotDiscoverItsProtectedAuditTrail(): void
    {
        [$managedCase, $lead, $organisation] = $this->createCaseWorkspace();
        $contributor = $this->createProfessional('case-contributor', $organisation, ProfessionalRole::Triage);
        $this->entityManager->persist(new CaseAssignment(
            Uuid::v7(),
            $managedCase,
            $contributor,
            CaseAssignmentRole::Contributor,
            $lead,
            new DateTimeImmutable(),
        ));
        $this->entityManager->flush();
        $this->client->loginUser($contributor);

        $this->client->request('GET', '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122());
        self::assertResponseIsSuccessful();
        self::assertFalse($this->responsePayload()['permissions']['viewAudit']);
        self::assertFalse($this->responsePayload()['permissions']['export']);

        $this->client->request('GET', '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122().'/export');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $this->client->request('GET', '/api/v1/professional/cases/'.$managedCase->id()->toRfc4122().'/audit-events');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $denied = $this->client->getResponse()->getContent();
        self::assertIsString($denied);

        $this->client->request('GET', '/api/v1/professional/cases/0192a5c0-9999-7000-8000-000000000046/audit-events');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertSame($denied, $this->client->getResponse()->getContent());
    }

    public function testOperationalViewsRemainAssignmentScopedAndUseStableCursors(): void
    {
        $now = new DateTimeImmutable('now');
        [$baselineCase, $lead, $organisation, , $sourceReport] = $this->createCaseWorkspace($now->modify('-6 days'));
        $source = new WorkflowSourceVersion(
            Uuid::v7(),
            'operational-view-'.Uuid::v7()->toRfc4122(),
            '2026.1',
            'Fictional operational-view source',
            'https://example.invalid/operational-view-source',
            'Andalucia',
            WorkflowSourceAuthority::Recommended,
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-01-02'),
        );
        $this->entityManager->persist($source);
        [$firstOverdue] = $this->createAssignedCase(
            $organisation,
            $lead,
            $source,
            $now->modify('-4 days'),
            $now->modify('-3 days'),
            CaseModality::Digital,
        );
        [$secondOverdue] = $this->createAssignedCase(
            $organisation,
            $lead,
            $source,
            $now->modify('-3 days'),
            $now->modify('-2 days'),
            CaseModality::Digital,
        );
        [$recent, $completedTask] = $this->createAssignedCase(
            $organisation,
            $lead,
            $source,
            $now->modify('-2 days'),
            $now->modify('+1 day'),
            CaseModality::Mixed,
        );
        $this->entityManager->flush();
        $completedTask->complete($lead, $now->modify('-1 minute'));
        $this->entityManager->flush();
        $this->client->loginUser($lead);

        $this->client->request('GET', '/api/v1/professional/cases?view=overdue&modality=digital&limit=1');
        self::assertResponseIsSuccessful();
        $firstPage = $this->responsePayload();
        self::assertSame($firstOverdue->id()->toRfc4122(), $firstPage['items'][0]['id']);
        self::assertSame(1, $firstPage['pagination']['limit']);
        self::assertIsString($firstPage['pagination']['nextCursor']);

        $this->client->request(
            'GET',
            '/api/v1/professional/cases?view=overdue&modality=digital&limit=1&cursor='
            .rawurlencode($firstPage['pagination']['nextCursor']),
        );
        self::assertResponseIsSuccessful();
        self::assertSame($secondOverdue->id()->toRfc4122(), $this->responsePayload()['items'][0]['id']);

        $this->client->request('GET', '/api/v1/professional/cases?view=recent');
        self::assertResponseIsSuccessful();
        self::assertSame($recent->id()->toRfc4122(), $this->responsePayload()['items'][0]['id']);

        $this->client->request('GET', '/api/v1/professional/cases?reference='.$recent->id()->toRfc4122());
        self::assertResponseIsSuccessful();
        self::assertSame([$recent->id()->toRfc4122()], array_column($this->responsePayload()['items'], 'id'));

        $this->client->request('GET', '/api/v1/professional/cases?reference='.$sourceReport->publicReference());
        self::assertResponseIsSuccessful();
        self::assertSame([$baselineCase->id()->toRfc4122()], array_column($this->responsePayload()['items'], 'id'));

        $this->client->request('GET', '/api/v1/professional/cases?status=active');
        self::assertResponseIsSuccessful();
        self::assertSame([], $this->responsePayload()['items']);

        $this->client->request('GET', '/api/v1/professional/cases/operational-summary');
        self::assertResponseIsSuccessful();
        self::assertSame(['assigned' => 4, 'overdue' => 3, 'upcoming' => 0], $this->responsePayload());

        $this->client->request('GET', '/api/v1/professional/cases?view=unknown');
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $this->client->request(
            'GET',
            '/api/v1/professional/cases?view=recent&cursor='.rawurlencode($firstPage['pagination']['nextCursor']),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testAnonymousRequestsCannotReadProfessionalCases(): void
    {
        $this->client->request('GET', '/api/v1/professional/cases');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
        self::assertStringContainsString(
            'no-store',
            (string) $this->client->getResponse()->headers->get('Cache-Control'),
        );
    }

    public function testNotificationIsReadOnlyForItsRecipientAndDisappearsAfterCaseAccessIsRevoked(): void
    {
        [$managedCase, $lead, $organisation] = $this->createCaseWorkspace();
        $recipient = $this->createProfessional('notification-recipient', $organisation, ProfessionalRole::Triage);
        $assignment = new CaseAssignment(Uuid::v7(), $managedCase, $recipient, CaseAssignmentRole::Contributor, $lead, new DateTimeImmutable());
        $notification = new ProfessionalNotification(Uuid::v7(), $recipient, $managedCase, ProfessionalNotificationType::CaseAssigned, new DateTimeImmutable());
        $this->entityManager->persist($assignment);
        $this->entityManager->persist($notification);
        $this->entityManager->flush();
        $this->client->loginUser($recipient);
        $this->client->request('GET', '/api/v1/professional/notifications');
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->responsePayload()['unreadCount']);
        self::assertSame('/profesionales/casos/'.$managedCase->id()->toRfc4122(), $this->responsePayload()['items'][0]['href']);
        $this->client->request('POST', '/api/v1/professional/notifications/'.$notification->id()->toRfc4122().'/read', server: $this->sameOriginHeaders());
        self::assertResponseIsSuccessful();
        self::assertNotNull($this->responsePayload()['readAt']);
        $activeAssignment = $this->entityManager->find(CaseAssignment::class, $assignment->id());
        self::assertInstanceOf(CaseAssignment::class, $activeAssignment);
        $activeAssignment->revokeAt(new DateTimeImmutable('+1 minute'), 'Fictional access change for notification boundary coverage.');
        $this->entityManager->flush();
        $this->client->request('GET', '/api/v1/professional/notifications');
        self::assertResponseIsSuccessful();
        self::assertSame([], $this->responsePayload()['items']);
        self::assertSame(0, $this->responsePayload()['unreadCount']);
    }

    public function testANotificationCannotBeReadByAProfessionalWhoIsNotItsRecipient(): void
    {
        [$managedCase, $lead, $organisation] = $this->createCaseWorkspace();
        $recipient = $this->createProfessional('notification-owner', $organisation, ProfessionalRole::Triage);
        $bystander = $this->createProfessional('notification-bystander', $organisation, ProfessionalRole::Triage);
        $this->entityManager->persist(new CaseAssignment(Uuid::v7(), $managedCase, $recipient, CaseAssignmentRole::Contributor, $lead, new DateTimeImmutable()));
        $this->entityManager->persist(new CaseAssignment(Uuid::v7(), $managedCase, $bystander, CaseAssignmentRole::Contributor, $lead, new DateTimeImmutable()));
        $notification = new ProfessionalNotification(Uuid::v7(), $recipient, $managedCase, ProfessionalNotificationType::CaseAssigned, new DateTimeImmutable());
        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $this->client->loginUser($bystander);
        $this->client->request('POST', '/api/v1/professional/notifications/'.$notification->id()->toRfc4122().'/read', server: $this->sameOriginHeaders());

        // The bystander can reach the case, so a leaked notification would only
        // be prevented by the recipient boundary itself.
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->client->request('GET', '/api/v1/professional/notifications');
        self::assertSame([], $this->responsePayload()['items']);
    }

    public function testReadStateSurvivesANewSessionForTheSameRecipient(): void
    {
        [$managedCase, $lead, $organisation] = $this->createCaseWorkspace();
        $recipient = $this->createProfessional('notification-reader', $organisation, ProfessionalRole::Triage);
        $this->entityManager->persist(new CaseAssignment(Uuid::v7(), $managedCase, $recipient, CaseAssignmentRole::Contributor, $lead, new DateTimeImmutable()));
        $notification = new ProfessionalNotification(Uuid::v7(), $recipient, $managedCase, ProfessionalNotificationType::CaseAssigned, new DateTimeImmutable());
        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $this->client->loginUser($recipient);
        $this->client->request('POST', '/api/v1/professional/notifications/'.$notification->id()->toRfc4122().'/read', server: $this->sameOriginHeaders());
        self::assertResponseIsSuccessful();
        $firstReadAt = $this->responsePayload()['readAt'];
        self::assertNotNull($firstReadAt);

        $this->client->loginUser($recipient);
        $this->client->request('GET', '/api/v1/professional/notifications');

        self::assertResponseIsSuccessful();
        self::assertSame(0, $this->responsePayload()['unreadCount']);
        // Doctrine's datetimetz mapping persists whole seconds, so the reloaded
        // acknowledgement is compared at the resolution actually stored.
        self::assertSame(
            $this->toPersistedSecond($firstReadAt),
            $this->toPersistedSecond($this->responsePayload()['items'][0]['readAt']),
        );
    }

    public function testAnOptionalNotificationPreferenceCanBeDisabledButARequiredOneCannot(): void
    {
        [, , $organisation] = $this->createCaseWorkspace();
        $professional = $this->createProfessional('notification-preferences', $organisation, ProfessionalRole::Triage);
        $this->entityManager->flush();
        $this->client->loginUser($professional);

        $this->client->request('GET', '/api/v1/professional/notification-preferences');
        self::assertResponseIsSuccessful();
        self::assertSame(
            [
                ['type' => 'case_assigned', 'enabled' => true, 'required' => true],
                ['type' => 'case_lifecycle_changed', 'enabled' => true, 'required' => false],
            ],
            $this->responsePayload()['items'],
        );

        $this->client->jsonRequest('PATCH', '/api/v1/professional/notification-preferences/case_lifecycle_changed', ['enabled' => false], $this->sameOriginHeaders());
        self::assertResponseIsSuccessful();
        self::assertFalse($this->responsePayload()['enabled']);

        $this->client->jsonRequest('PATCH', '/api/v1/professional/notification-preferences/case_assigned', ['enabled' => false], $this->sameOriginHeaders());
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $this->client->request('GET', '/api/v1/professional/notification-preferences');
        self::assertSame(
            [
                ['type' => 'case_assigned', 'enabled' => true, 'required' => true],
                ['type' => 'case_lifecycle_changed', 'enabled' => false, 'required' => false],
            ],
            $this->responsePayload()['items'],
        );
    }

    /** @return array{ManagedCase, Professional, Organisation, ReportAttachment, Report} */
    private function createCaseWorkspace(?DateTimeImmutable $now = null): array
    {
        $organisation = $this->createOrganisation('46A', 'Case Workspace School');
        $lead = $this->createProfessional('case-lead', $organisation, ProfessionalRole::Triage);
        $now ??= new DateTimeImmutable('now');
        $managedCase = new ManagedCase(Uuid::v7(), $organisation, $lead, $now, CaseModality::Mixed);
        $assignment = new CaseAssignment(
            Uuid::v7(),
            $managedCase,
            $lead,
            CaseAssignmentRole::Lead,
            $lead,
            $now,
        );
        $person = new CaseInvolvedPerson(
            Uuid::v7(),
            $managedCase,
            CaseInvolvedPersonName::fromString('Fictional affected person'),
            CaseInvolvedPersonRole::Affected,
            $lead,
            $now,
        );
        $source = new WorkflowSourceVersion(
            Uuid::v7(),
            'workspace-test-'.Uuid::v7()->toRfc4122(),
            '2026.1',
            'Fictional reviewed workflow source',
            'https://example.invalid/workflow-source',
            'Andalucia',
            WorkflowSourceAuthority::Binding,
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-01-02'),
        );
        $task = new CaseTask(
            Uuid::v7(),
            $managedCase,
            $lead,
            $source,
            CaseProtocolStage::InspectionCommunication,
            CaseTaskKind::ExternalCommunication,
            'Record the fictional inspection communication.',
            $now->modify('+1 day'),
            $lead,
            $now,
        );

        $report = Report::create(
            $organisation,
            SituationDescription::fromString('A fictional case source report used only for the protected workspace test.'),
            SituationContext::Mixed,
        )->report;
        $decision = new ReportTriageDecision(
            Uuid::v7(),
            $report,
            $lead,
            ReportTriageOutcome::LinkToCase,
            ReportTriageReason::fromString('A fictional triage decision opened this managed case.'),
            $now,
            $managedCase,
        );

        foreach ([$managedCase, $assignment, $person, $source, $task, $report, $decision] as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        $attachment = $this->persistAvailableAttachment($report);

        return [$managedCase, $lead, $organisation, $attachment, $report];
    }

    /** @return array{ManagedCase, CaseTask} */
    private function createAssignedCase(
        Organisation $organisation,
        Professional $lead,
        WorkflowSourceVersion $source,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $dueAt,
        CaseModality $modality,
    ): array {
        $managedCase = new ManagedCase(Uuid::v7(), $organisation, $lead, $createdAt, $modality);
        $assignment = new CaseAssignment(
            Uuid::v7(),
            $managedCase,
            $lead,
            CaseAssignmentRole::Lead,
            $lead,
            $createdAt,
        );
        $task = new CaseTask(
            Uuid::v7(),
            $managedCase,
            $lead,
            $source,
            CaseProtocolStage::Assessment,
            CaseTaskKind::InternalAction,
            'Fictional operational case task.',
            $dueAt,
            $lead,
            $createdAt,
        );
        foreach ([$managedCase, $assignment, $task] as $entity) {
            $this->entityManager->persist($entity);
        }

        return [$managedCase, $task];
    }

    private function createOrganisation(string $suffix, string $name): Organisation
    {
        $organisation = new Organisation(
            Uuid::v7(),
            $name,
            PublicReportingIdentifier::fromString('ORG_1NB46'.str_pad($suffix, 11, '0')),
        );
        $this->entityManager->persist($organisation);
        $this->entityManager->flush();

        return $organisation;
    }

    private function toPersistedSecond(string $timestamp): string
    {
        return (new DateTimeImmutable($timestamp))->format('Y-m-d\TH:i:sP');
    }

    /** @return array<string, string> */
    private function sameOriginHeaders(): array
    {
        return ['HTTP_ORIGIN' => 'http://localhost', 'HTTP_SEC_FETCH_SITE' => 'same-origin'];
    }

    private function activeAssignmentId(ManagedCase $managedCase, Professional $professional): string
    {
        $id = $this->entityManager->getConnection()->fetchOne(
            'SELECT id FROM case_assignments WHERE case_id = :caseId AND professional_id = :professionalId AND revoked_at IS NULL',
            ['caseId' => $managedCase->id()->toRfc4122(), 'professionalId' => $professional->id()->toRfc4122()],
        );
        self::assertIsString($id);

        return $id;
    }

    private function createProfessional(
        string $name,
        Organisation $organisation,
        ProfessionalRole $role,
    ): Professional {
        $professional = new Professional(
            Uuid::v7(),
            ucfirst($name).' Professional',
            ProfessionalEmail::fromString($name.'@case-workspace-test.example'),
            new DateTimeImmutable(),
        );
        $membership = new OrganisationMembership(
            Uuid::v7(),
            $professional,
            $organisation,
            $role,
            new DateTimeImmutable(),
        );
        $this->entityManager->persist($professional);
        $this->entityManager->persist($membership);
        $this->entityManager->flush();

        return $professional;
    }

    /** @return array<string, mixed> */
    private function responsePayload(): array
    {
        $content = $this->client->getResponse()->getContent();
        self::assertIsString($content);

        return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }

    private function persistAvailableAttachment(Report $report): ReportAttachment
    {
        $path = tempnam(sys_get_temp_dir(), 'convive-case-workspace-');
        self::assertNotFalse($path);
        $this->temporaryPaths[] = $path;
        self::assertSame(30, file_put_contents($path, '%PDF-1.7\nfake case evidence\n'));

        $id = Uuid::v7();
        $this->storedAttachmentIds[] = $id->toRfc4122();
        $stored = $this->storage->storeQuarantine($id, $path);
        $attachment = ReportAttachment::quarantine(
            $id,
            $report,
            AttachmentMediaType::Pdf,
            $stored->byteSize,
            $stored->contentHash,
            new DateTimeImmutable(),
            AttachmentDescription::fromNullable('Fictional case evidence.'),
        );
        $this->attachments->saveQuarantinedWithReportCapacity([$attachment]);
        $attachment->beginScanning(new DateTimeImmutable());
        $this->storage->promoteToAvailable($attachment);
        $attachment->markAvailable(new DateTimeImmutable());
        $this->attachments->save($attachment);

        return $attachment;
    }

    private function uniqueTestClientIp(): string
    {
        return sprintf('198.18.%d.%d', random_int(0, 255), random_int(0, 255));
    }
}
