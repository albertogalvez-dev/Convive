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
