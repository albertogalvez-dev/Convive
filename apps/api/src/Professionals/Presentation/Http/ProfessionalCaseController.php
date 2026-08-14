<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Cases\Application\CaseWorkspaceDetail;
use App\Cases\Application\CaseWorkspaceSummary;
use App\Cases\Application\CasePdfRenderer;
use App\Cases\Application\AuthoriseCaseAccess;
use App\Cases\Application\CompleteCaseTask;
use App\Cases\Application\CreateCaseTask;
use App\Cases\Application\MarkCaseTaskNotApplicable;
use App\Cases\Application\ManageCaseAssignment;
use App\Cases\Application\ProfessionalCaseWorkspace;
use App\Cases\Domain\CaseAccessDenied;
use App\Cases\Domain\CaseAssignment;
use App\Cases\Domain\CaseAssignmentRole;
use App\Cases\Domain\CaseAuditAction;
use App\Cases\Domain\CaseAuditEvent;
use App\Cases\Domain\CaseAuditEventRepository;
use App\Cases\Domain\CaseAuditTarget;
use App\Cases\Domain\CaseInvolvedPerson;
use App\Cases\Domain\CaseModality;
use App\Cases\Domain\CaseOperationalView;
use App\Cases\Domain\CasePermission;
use App\Cases\Domain\CaseStatus;
use App\Cases\Domain\CaseTask;
use App\Cases\Domain\CaseTaskKind;
use App\Cases\Domain\CaseTaskRepository;
use App\Cases\Domain\CaseWorkspaceQuery;
use App\Cases\Domain\CaseProtocolStage;
use App\Cases\Domain\WorkflowSourceVersionRepository;
use App\Cases\Domain\ProfessionalExportEvent;
use App\Cases\Domain\ProfessionalExportEventRepository;
use App\Cases\Domain\ProfessionalExportKind;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalRepository;
use App\Professionals\Domain\OrganisationMembershipRepository;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Presentation\Http\PrivateReportAttachmentDownloadResponder;
use App\Reporting\Presentation\Http\ReportAttachmentUnavailableHttpException;
use App\Shared\Infrastructure\Logging\SecurityEventLogger;
use App\Shared\Presentation\Http\RateLimitEnforcer;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use DateMalformedStringException;
use InvalidArgumentException;
use LogicException;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final readonly class ProfessionalCaseController
{
    private const DEFAULT_PAGE_LIMIT = 20;
    private const MAXIMUM_PAGE_LIMIT = 50;

    public function __construct(
        private ProfessionalCaseWorkspace $workspace,
        private ProfessionalCaseCursorCodec $cursorCodec,
        private AuthoriseCaseAccess $authorise,
        private CreateCaseTask $createCaseTask,
        private CompleteCaseTask $completeCaseTask,
        private MarkCaseTaskNotApplicable $markCaseTaskNotApplicable,
        private ManageCaseAssignment $manageCaseAssignment,
        private ProfessionalRepository $professionals,
        private OrganisationMembershipRepository $memberships,
        private CaseTaskRepository $tasks,
        private WorkflowSourceVersionRepository $workflowSources,
        private CaseAuditEventRepository $auditEvents,
        private ProfessionalExportEventRepository $professionalExportEvents,
        private CasePdfRenderer $pdfRenderer,
        private PrivateReportAttachmentDownloadResponder $downloadResponder,
        private SecurityEventLogger $securityEventLogger,
        private RateLimitEnforcer $rateLimitEnforcer,
        #[Autowire(service: 'limiter.professional_attachment_download_ip')]
        private RateLimiterFactory $attachmentDownloadRateLimiter,
    ) {
    }

    #[Route('/api/v1/professional/cases', name: 'api_v1_professional_list_cases', methods: ['GET'])]
    #[OA\Get(
        operationId: 'listProfessionalCases',
        summary: 'List operational views of cases assigned to the current professional',
        security: [['professionalSession' => []]],
        tags: ['Professional cases'],
        parameters: [
            new OA\Parameter(name: 'view', in: 'query', schema: new OA\Schema(type: 'string', enum: ['assigned', 'overdue', 'upcoming', 'recent'])),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['assessment', 'active', 'closed'])),
            new OA\Parameter(name: 'modality', in: 'query', schema: new OA\Schema(type: 'string', enum: ['in_person', 'digital', 'mixed', 'unknown'])),
            new OA\Parameter(name: 'reference', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 64)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: self::MAXIMUM_PAGE_LIMIT)),
            new OA\Parameter(name: 'cursor', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 512)),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'The bounded case workspace overview.',
                content: new OA\JsonContent(ref: '#/components/schemas/ProfessionalCasePage'),
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'A filter or cursor is invalid.'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
        ],
    )]
    public function list(#[CurrentUser] Professional $professional, Request $request): JsonResponse
    {
        $now = DateTimeImmutable::createFromTimestamp(microtime(true));
        $query = $this->parseWorkspaceQuery($request, $now);
        $page = $this->workspace->page($professional, $query);

        return $this->json([
            'items' => array_map($this->serializeSummary(...), $page->items),
            'pagination' => [
                'limit' => $query->limit,
                'nextCursor' => $page->nextCursor === null ? null : $this->cursorCodec->encode($page->nextCursor),
            ],
        ]);
    }

    #[Route('/api/v1/professional/cases/operational-summary', name: 'api_v1_professional_case_operational_summary', methods: ['GET'])]
    #[OA\Get(
        operationId: 'getProfessionalCaseOperationalSummary',
        summary: 'Read permission-preserving case operational counts',
        security: [['professionalSession' => []]],
        tags: ['Professional cases'],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Counts derived only from exact active case assignments.', content: new OA\JsonContent(ref: '#/components/schemas/ProfessionalCaseOperationalSummary')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
        ],
    )]
    public function operationalSummary(#[CurrentUser] Professional $professional): JsonResponse
    {
        return $this->json($this->workspace->operationalCounts(
            $professional,
            DateTimeImmutable::createFromTimestamp(microtime(true)),
        ));
    }

    #[Route('/api/v1/professional/cases/{id}', name: 'api_v1_professional_get_case', methods: ['GET'])]
    #[OA\Get(
        operationId: 'getProfessionalCase',
        summary: 'Read an assigned professional case workspace',
        security: [['professionalSession' => []]],
        tags: ['Professional cases'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'The permission-scoped case workspace.',
                content: new OA\JsonContent(ref: '#/components/schemas/ProfessionalCaseDetail'),
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The case is unavailable in this scope.'),
        ],
    )]
    public function detail(string $id, #[CurrentUser] Professional $professional): JsonResponse
    {
        $detail = $this->resolveDetail($id, $professional);
        $now = DateTimeImmutable::createFromTimestamp(microtime(true));

        return $this->json($this->serializeDetail($detail, $now));
    }

    #[Route('/api/v1/professional/cases/{id}/assignments', name: 'api_v1_professional_assign_case_professional', methods: ['POST'])]
    #[OA\Post(
        operationId: 'assignProfessionalCaseCollaborator',
        summary: 'Explicitly assign a contributor or observer to an exact case',
        description: 'Requires exact-case assignment management. Lead responsibility changes require the explicit handover endpoint.',
        security: [['professionalSession' => []]],
        tags: ['Professional cases'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ManageProfessionalCaseAssignmentRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'The explicit assignment was recorded.', content: new OA\JsonContent(ref: '#/components/schemas/ProfessionalCaseAssignment')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The case or target professional is unavailable in this scope.'),
            new OA\Response(response: Response::HTTP_CONFLICT, description: 'The assignment conflicts with the current case access.'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The assignment request is invalid.'),
        ],
    )]
    public function assignProfessional(
        string $id,
        #[CurrentUser] Professional $professional,
        #[MapRequestPayload(acceptFormat: 'json')]
        ManageProfessionalCaseAssignmentRequest $payload,
    ): JsonResponse {
        $detail = $this->resolveDetail($id, $professional);
        $target = Uuid::isValid($payload->professionalId) ? $this->professionals->find(Uuid::fromString($payload->professionalId)) : null;
        if (!$target instanceof Professional) {
            throw new ProfessionalCaseNotFoundHttpException();
        }
        try {
            $assignment = $this->manageCaseAssignment->assign($detail->managedCase, $target, CaseAssignmentRole::from($payload->role), $payload->reason, $professional, DateTimeImmutable::createFromTimestamp(microtime(true)));
        } catch (CaseAccessDenied $exception) {
            throw new ProfessionalCaseNotFoundHttpException(previous: $exception);
        } catch (InvalidArgumentException|LogicException|UniqueConstraintViolationException $exception) {
            throw new ProfessionalCaseAssignmentConflictHttpException(previous: $exception);
        }

        return $this->json($this->serializeAssignment($assignment), Response::HTTP_CREATED);
    }

    #[Route('/api/v1/professional/cases/{id}/assignments/{assignmentId}/handover', name: 'api_v1_professional_handover_case_lead', methods: ['POST'])]
    #[OA\Post(
        operationId: 'handoverProfessionalCaseLead',
        summary: 'Atomically hand over exact-case lead responsibility',
        description: 'Requires exact-case assignment management and a recorded reason. The former lead is revoked as the new lead is created.',
        security: [['professionalSession' => []]],
        tags: ['Professional cases'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/HandoverProfessionalCaseAssignmentRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'The lead handover was recorded.', content: new OA\JsonContent(ref: '#/components/schemas/ProfessionalCaseAssignment')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The case, assignment or target professional is unavailable in this scope.'),
            new OA\Response(response: Response::HTTP_CONFLICT, description: 'The handover conflicts with the current case access.'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The handover request is invalid.'),
        ],
    )]
    public function handoverAssignment(
        string $id,
        string $assignmentId,
        #[CurrentUser] Professional $professional,
        #[MapRequestPayload(acceptFormat: 'json')]
        HandoverProfessionalCaseAssignmentRequest $payload,
    ): JsonResponse {
        $detail = $this->resolveDetail($id, $professional);
        $formerLead = $this->findAssignment($detail, $assignmentId);
        $target = Uuid::isValid($payload->professionalId) ? $this->professionals->find(Uuid::fromString($payload->professionalId)) : null;
        if (!$target instanceof Professional) {
            throw new ProfessionalCaseNotFoundHttpException();
        }
        try {
            $assignment = $this->manageCaseAssignment->handover($formerLead, $target, $payload->reason, $professional, DateTimeImmutable::createFromTimestamp(microtime(true)));
        } catch (CaseAccessDenied $exception) {
            throw new ProfessionalCaseNotFoundHttpException(previous: $exception);
        } catch (LogicException|UniqueConstraintViolationException $exception) {
            throw new ProfessionalCaseAssignmentConflictHttpException(previous: $exception);
        }

        return $this->json($this->serializeAssignment($assignment), Response::HTTP_CREATED);
    }

    #[Route('/api/v1/professional/cases/{id}/assignments/{assignmentId}/role', name: 'api_v1_professional_change_case_assignment_role', methods: ['POST'])]
    #[OA\Post(
        operationId: 'changeProfessionalCaseAssignmentRole',
        summary: 'Change a contributor or observer exact-case assignment',
        description: 'Requires exact-case assignment management and a recorded reason. Lead responsibility can only change through the explicit handover endpoint.',
        security: [['professionalSession' => []]],
        tags: ['Professional cases'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ChangeProfessionalCaseAssignmentRoleRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'The assignment role was changed.', content: new OA\JsonContent(ref: '#/components/schemas/ProfessionalCaseAssignment')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The case or assignment is unavailable in this scope.'),
            new OA\Response(response: Response::HTTP_CONFLICT, description: 'The requested role change conflicts with the current case access.'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The assignment role request is invalid.'),
        ],
    )]
    public function changeAssignmentRole(
        string $id,
        string $assignmentId,
        #[CurrentUser] Professional $professional,
        #[MapRequestPayload(acceptFormat: 'json')]
        ChangeProfessionalCaseAssignmentRoleRequest $payload,
    ): JsonResponse {
        $detail = $this->resolveDetail($id, $professional);
        $assignment = $this->findAssignment($detail, $assignmentId);
        try {
            $this->manageCaseAssignment->changeRole($assignment, CaseAssignmentRole::from($payload->role), $payload->reason, $professional, DateTimeImmutable::createFromTimestamp(microtime(true)));
        } catch (CaseAccessDenied $exception) {
            throw new ProfessionalCaseNotFoundHttpException(previous: $exception);
        } catch (InvalidArgumentException|LogicException|UniqueConstraintViolationException $exception) {
            throw new ProfessionalCaseAssignmentConflictHttpException(previous: $exception);
        }

        return $this->json($this->serializeAssignment($assignment));
    }

    #[Route('/api/v1/professional/cases/{id}/assignments/{assignmentId}/revoke', name: 'api_v1_professional_revoke_case_assignment', methods: ['POST'])]
    #[OA\Post(
        operationId: 'revokeProfessionalCaseAssignment',
        summary: 'Explicitly revoke a non-lead exact-case assignment',
        description: 'Requires exact-case assignment management and a recorded reason. The final lead must use the explicit handover endpoint.',
        security: [['professionalSession' => []]],
        tags: ['Professional cases'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/RevokeProfessionalCaseAssignmentRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'The assignment was revoked.'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The case or assignment is unavailable in this scope.'),
            new OA\Response(response: Response::HTTP_CONFLICT, description: 'The final lead requires an explicit handover.'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The revocation request is invalid.'),
        ],
    )]
    public function revokeAssignment(
        string $id,
        string $assignmentId,
        #[CurrentUser] Professional $professional,
        #[MapRequestPayload(acceptFormat: 'json')]
        RevokeProfessionalCaseAssignmentRequest $payload,
    ): JsonResponse
    {
        $detail = $this->resolveDetail($id, $professional);
        $assignment = $this->findAssignment($detail, $assignmentId);
        try {
            $this->manageCaseAssignment->revoke($assignment, $payload->reason, $professional, DateTimeImmutable::createFromTimestamp(microtime(true)));
        } catch (CaseAccessDenied $exception) {
            throw new ProfessionalCaseNotFoundHttpException(previous: $exception);
        } catch (LogicException|UniqueConstraintViolationException $exception) {
            throw new ProfessionalCaseAssignmentConflictHttpException(previous: $exception);
        }

        return $this->json(['id' => $assignment->id()->toRfc4122(), 'revoked' => true]);
    }

    private function findAssignment(CaseWorkspaceDetail $detail, string $assignmentId): CaseAssignment
    {
        foreach ($detail->assignments as $assignment) {
            if ($assignment->id()->toRfc4122() === $assignmentId) {
                return $assignment;
            }
        }

        throw new ProfessionalCaseNotFoundHttpException();
    }

    #[Route('/api/v1/professional/cases/{id}/tasks', name: 'api_v1_professional_create_case_task', methods: ['POST'])]
    #[OA\Post(
        operationId: 'createProfessionalCaseTask',
        summary: 'Create an explicit source-aware task in an assigned case',
        description: 'Requires exact-case manage permission. This does not apply a protocol or confirm an external communication.',
        security: [['professionalSession' => []]],
        tags: ['Professional cases'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CreateProfessionalCaseTaskRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'The explicit task was created.', content: new OA\JsonContent(ref: '#/components/schemas/ProfessionalCaseTask')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The case is unavailable in this scope.'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The task request is invalid.'),
        ],
    )]
    public function createTask(
        string $id,
        #[CurrentUser] Professional $professional,
        #[MapRequestPayload(acceptFormat: 'json')]
        CreateProfessionalCaseTaskRequest $payload,
    ): JsonResponse {
        $detail = $this->resolveDetail($id, $professional);
        $owner = $this->assignedProfessional($detail, $payload->ownerId);
        $source = Uuid::isValid($payload->sourceId) ? $this->workflowSources->find(Uuid::fromString($payload->sourceId)) : null;
        if ($owner === null || $source === null) {
            throw new ProfessionalCaseNotFoundHttpException();
        }

        try {
            $task = $this->createCaseTask->create(
                Uuid::v7(),
                $detail->managedCase,
                $owner,
                $source,
                CaseProtocolStage::from($payload->stage),
                CaseTaskKind::from($payload->kind),
                $payload->title,
                new DateTimeImmutable($payload->dueAt),
                $professional,
                DateTimeImmutable::createFromTimestamp(microtime(true)),
            );
        } catch (InvalidArgumentException|DateMalformedStringException $exception) {
            throw new InvalidProfessionalCaseTaskHttpException(previous: $exception);
        } catch (CaseAccessDenied $exception) {
            throw new ProfessionalCaseNotFoundHttpException(previous: $exception);
        }

        return $this->json($this->serializeTask($task, DateTimeImmutable::createFromTimestamp(microtime(true))), Response::HTTP_CREATED);
    }

    #[Route('/api/v1/professional/cases/{id}/tasks/{taskId}/complete', name: 'api_v1_professional_complete_case_task', methods: ['POST'])]
    #[OA\Post(
        operationId: 'completeProfessionalCaseTask',
        summary: 'Explicitly complete a pending case task',
        security: [['professionalSession' => []]],
        tags: ['Professional cases'],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'The task was completed.', content: new OA\JsonContent(ref: '#/components/schemas/ProfessionalCaseTask')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The case or task is unavailable in this scope.'),
            new OA\Response(response: Response::HTTP_CONFLICT, description: 'The task has already reached a terminal state.'),
        ],
    )]
    public function completeTask(string $id, string $taskId, #[CurrentUser] Professional $professional): JsonResponse
    {
        $task = $this->resolveTask($id, $taskId, $professional);
        $now = DateTimeImmutable::createFromTimestamp(microtime(true));
        try {
            $this->completeCaseTask->complete($task, $professional, $now);
        } catch (CaseAccessDenied $exception) {
            throw new ProfessionalCaseNotFoundHttpException(previous: $exception);
        } catch (LogicException $exception) {
            throw new ProfessionalCaseTaskConflictHttpException(previous: $exception);
        }

        return $this->json($this->serializeTask($task, $now));
    }

    #[Route('/api/v1/professional/cases/{id}/tasks/{taskId}/not-applicable', name: 'api_v1_professional_mark_case_task_not_applicable', methods: ['POST'])]
    #[OA\Post(
        operationId: 'markProfessionalCaseTaskNotApplicable',
        summary: 'Explicitly mark a pending case task not applicable with a reason',
        security: [['professionalSession' => []]],
        tags: ['Professional cases'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/MarkProfessionalCaseTaskNotApplicableRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'The task was marked not applicable.', content: new OA\JsonContent(ref: '#/components/schemas/ProfessionalCaseTask')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The case or task is unavailable in this scope.'),
            new OA\Response(response: Response::HTTP_CONFLICT, description: 'The task has already reached a terminal state.'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The resolution reason is invalid.'),
        ],
    )]
    public function markTaskNotApplicable(
        string $id,
        string $taskId,
        #[CurrentUser] Professional $professional,
        #[MapRequestPayload(acceptFormat: 'json')]
        MarkProfessionalCaseTaskNotApplicableRequest $payload,
    ): JsonResponse {
        $task = $this->resolveTask($id, $taskId, $professional);
        $now = DateTimeImmutable::createFromTimestamp(microtime(true));
        try {
            $this->markCaseTaskNotApplicable->mark($task, $professional, $now, $payload->reason);
        } catch (CaseAccessDenied $exception) {
            throw new ProfessionalCaseNotFoundHttpException(previous: $exception);
        } catch (LogicException $exception) {
            throw new ProfessionalCaseTaskConflictHttpException(previous: $exception);
        }

        return $this->json($this->serializeTask($task, $now));
    }

    #[Route(
        '/api/v1/professional/cases/{id}/evidence/{attachmentId}/download',
        name: 'api_v1_professional_download_case_evidence',
        methods: ['GET'],
    )]
    #[OA\Get(
        operationId: 'downloadProfessionalCaseEvidence',
        summary: 'Download available evidence from an assigned case',
        description: 'Streams one available private attachment through the exact case-assignment boundary.',
        security: [['professionalSession' => []]],
        tags: ['Professional cases'],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'The authorised evidence stream.'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The case or evidence is unavailable in this scope.'),
            new OA\Response(response: Response::HTTP_TOO_MANY_REQUESTS, description: 'The download rate or concurrency limit was exceeded.'),
        ],
    )]
    public function downloadEvidence(
        string $id,
        string $attachmentId,
        #[CurrentUser] Professional $professional,
        Request $request,
    ): StreamedResponse {
        $this->rateLimitEnforcer->enforce(
            $this->attachmentDownloadRateLimiter,
            'professional_case_evidence_download_ip',
            $request,
        );
        $detail = $this->resolveDetail($id, $professional);

        if (!Uuid::isValid($attachmentId)) {
            $this->securityEventLogger->professionalAttachmentDownloadDenied($request);
            throw new ProfessionalCaseNotFoundHttpException();
        }

        $attachment = null;
        foreach ($detail->evidence as $candidate) {
            if ($candidate->id()->equals(Uuid::fromString($attachmentId))) {
                $attachment = $candidate;
                break;
            }
        }

        if (!$attachment instanceof ReportAttachment) {
            $this->securityEventLogger->professionalAttachmentDownloadDenied($request);
            throw new ProfessionalCaseNotFoundHttpException();
        }

        try {
            $response = $this->downloadResponder->respond($attachment);
        } catch (ReportAttachmentUnavailableHttpException $exception) {
            $this->securityEventLogger->professionalAttachmentDownloadDenied($request);

            throw new ProfessionalCaseNotFoundHttpException(previous: $exception);
        }
        $this->securityEventLogger->professionalAttachmentDownloaded($request);
        $this->auditEvents->append(new CaseAuditEvent(
            Uuid::v7(),
            $detail->managedCase,
            $professional,
            CaseAuditAction::EvidenceDownloadAuthorised,
            CaseAuditTarget::Attachment,
            $attachment->id(),
            DateTimeImmutable::createFromTimestamp(microtime(true)),
        ));
        $this->auditEvents->flush();

        return $response;
    }

    #[Route(
        '/api/v1/professional/cases/{id}/audit-events',
        name: 'api_v1_professional_list_case_audit_events',
        methods: ['GET'],
    )]
    #[OA\Get(
        operationId: 'listProfessionalCaseAuditEvents',
        summary: 'Read the explicit audit trail for an assigned case',
        description: 'Requires the separate case audit permission and returns no case content or target identifiers.',
        security: [['professionalSession' => []]],
        tags: ['Professional cases'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'The minimised ordered audit events.',
                content: new OA\JsonContent(ref: '#/components/schemas/ProfessionalCaseAuditEventPage'),
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The case is unavailable in this scope.'),
        ],
    )]
    public function auditEvents(string $id, #[CurrentUser] Professional $professional): JsonResponse
    {
        $detail = $this->resolveAuditDetail($id, $professional);

        return $this->json([
            'items' => array_map($this->serializeAuditEvent(...), $this->auditEvents->findByCase($detail->managedCase)),
        ]);
    }

    #[Route(
        '/api/v1/professional/cases/{id}/audit-events/export',
        name: 'api_v1_professional_export_case_audit_events',
        methods: ['GET'],
    )]
    #[OA\Get(
        operationId: 'exportProfessionalCaseAuditEvents',
        summary: 'Export the minimal audit trail for an assigned case',
        description: 'Requires the separate case audit permission. The CSV excludes case content, people, evidence, reasons and target identifiers.',
        security: [['professionalSession' => []]],
        tags: ['Professional cases'],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'The minimised audit CSV.'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The case is unavailable in this scope.'),
        ],
    )]
    public function exportAuditEvents(string $id, #[CurrentUser] Professional $professional): StreamedResponse
    {
        $detail = $this->resolveAuditDetail($id, $professional);
        $now = DateTimeImmutable::createFromTimestamp(microtime(true));
        $this->auditEvents->append(new CaseAuditEvent(
            Uuid::v7(),
            $detail->managedCase,
            $professional,
            CaseAuditAction::AuditExported,
            CaseAuditTarget::AuditTrail,
            $detail->managedCase->id(),
            $now,
        ));
        $this->auditEvents->flush();
        $events = $this->auditEvents->findByCase($detail->managedCase);

        return new StreamedResponse(
            static function () use ($events): void {
                $output = fopen('php://output', 'wb');
                if ($output === false) {
                    throw new \RuntimeException('The audit export could not be opened.');
                }

                fputcsv($output, ['occurred_at', 'action', 'target', 'actor'], ',', '"', '');
                foreach ($events as $event) {
                    fputcsv($output, [
                        $event->occurredAt()->format(DATE_RFC3339_EXTENDED),
                        $event->action()->value,
                        $event->target()->value,
                        $event->actor()->name(),
                    ], ',', '"', '');
                }
                fclose($output);
            },
            Response::HTTP_OK,
            [
                'Cache-Control' => 'no-store',
                'Content-Disposition' => 'attachment; filename="case-audit.csv"',
                'Content-Type' => 'text/csv; charset=utf-8',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    #[Route(
        '/api/v1/professional/cases/{id}/export',
        name: 'api_v1_professional_export_case_record',
        methods: ['GET'],
    )]
    #[OA\Get(
        operationId: 'exportProfessionalCaseRecord',
        summary: 'Export a minimised lead-authorised case record as PDF',
        security: [['professionalSession' => []]],
        tags: ['Professional cases'],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'The controlled PDF record.'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The case is unavailable in this scope.'),
        ],
    )]
    public function exportCaseRecord(string $id, #[CurrentUser] Professional $professional): Response
    {
        $detail = $this->resolveExportDetail($id, $professional);
        $pdf = $this->pdfRenderer->caseRecord($detail, $this->auditEvents->findByCase($detail->managedCase));
        $this->auditEvents->append(new CaseAuditEvent(
            Uuid::v7(),
            $detail->managedCase,
            $professional,
            CaseAuditAction::CaseRecordExported,
            CaseAuditTarget::CaseRecord,
            $detail->managedCase->id(),
            DateTimeImmutable::createFromTimestamp(microtime(true)),
        ));
        $this->auditEvents->flush();

        return $this->pdfResponse($pdf, 'case-record.pdf');
    }

    #[Route(
        '/api/v1/professional/cases/operational-overview/export',
        name: 'api_v1_professional_export_operational_overview',
        methods: ['GET'],
        priority: 10,
    )]
    #[OA\Get(
        operationId: 'exportProfessionalOperationalOverview',
        summary: 'Export non-identifying authorised operational counts as PDF',
        security: [['professionalSession' => []]],
        tags: ['Professional cases'],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'The non-identifying PDF overview.'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
        ],
    )]
    public function exportOperationalOverview(#[CurrentUser] Professional $professional): Response
    {
        $now = DateTimeImmutable::createFromTimestamp(microtime(true));
        $pdf = $this->pdfRenderer->operationalOverview($this->workspace->operationalCounts($professional, $now));
        $this->professionalExportEvents->append(new ProfessionalExportEvent(
            Uuid::v7(),
            $professional,
            ProfessionalExportKind::OperationalOverview,
            $now,
        ));
        $this->professionalExportEvents->flush();

        return $this->pdfResponse($pdf, 'operational-overview.pdf');
    }

    private function pdfResponse(string $pdf, string $filename): Response
    {
        return new Response($pdf, Response::HTTP_OK, [
            'Cache-Control' => 'no-store',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, noarchive',
        ]);
    }

    private function parseWorkspaceQuery(Request $request, DateTimeImmutable $now): CaseWorkspaceQuery
    {
        $view = CaseOperationalView::tryFrom($request->query->getString('view') ?: CaseOperationalView::Assigned->value)
            ?? throw new BadRequestHttpException('The case view is invalid.');
        $statusValue = $request->query->getString('status');
        $status = $statusValue === '' ? null : CaseStatus::tryFrom($statusValue);
        if ($statusValue !== '' && $status === null) {
            throw new BadRequestHttpException('The case status filter is invalid.');
        }
        $modalityValue = $request->query->getString('modality');
        $modality = $modalityValue === '' ? null : CaseModality::tryFrom($modalityValue);
        if ($modalityValue !== '' && $modality === null) {
            throw new BadRequestHttpException('The case modality filter is invalid.');
        }

        $reference = trim($request->query->getString('reference'));
        if (mb_strlen($reference) > 64) {
            throw new BadRequestHttpException('The case reference filter is invalid.');
        }
        $reference = $reference === '' ? null : strtoupper($reference);

        $limitValue = $request->query->get('limit');
        if ($limitValue === null || $limitValue === '') {
            $limit = self::DEFAULT_PAGE_LIMIT;
        } elseif (filter_var($limitValue, FILTER_VALIDATE_INT) === false) {
            throw new BadRequestHttpException('The case page limit is invalid.');
        } else {
            $limit = (int) $limitValue;
        }
        if ($limit < 1 || $limit > self::MAXIMUM_PAGE_LIMIT) {
            throw new BadRequestHttpException('The case page limit is invalid.');
        }

        $cursorValue = $request->query->getString('cursor');
        $cursor = $cursorValue === '' ? null : $this->cursorCodec->decode($cursorValue);
        if ($cursorValue !== '' && ($cursor === null || $cursor->view !== $view)) {
            throw new BadRequestHttpException('The case cursor is invalid.');
        }

        return new CaseWorkspaceQuery($view, $status, $modality, $reference, $cursor, $limit, $now);
    }

    private function resolveDetail(string $id, Professional $professional): CaseWorkspaceDetail
    {
        if (!Uuid::isValid($id)) {
            throw new ProfessionalCaseNotFoundHttpException();
        }

        return $this->workspace->detail(Uuid::fromString($id), $professional)
            ?? throw new ProfessionalCaseNotFoundHttpException();
    }

    private function resolveTask(string $caseId, string $taskId, Professional $professional): CaseTask
    {
        $detail = $this->resolveDetail($caseId, $professional);
        if (!Uuid::isValid($taskId)) {
            throw new ProfessionalCaseNotFoundHttpException();
        }

        $task = $this->tasks->find(Uuid::fromString($taskId));
        if (!$task instanceof CaseTask || !$task->managedCase()->id()->equals($detail->managedCase->id())) {
            throw new ProfessionalCaseNotFoundHttpException();
        }

        return $task;
    }

    private function assignedProfessional(CaseWorkspaceDetail $detail, string $professionalId): ?Professional
    {
        if (!Uuid::isValid($professionalId)) {
            return null;
        }

        foreach ($detail->assignments as $assignment) {
            if ($assignment->professional()->id()->equals(Uuid::fromString($professionalId))) {
                return $assignment->professional();
            }
        }

        return null;
    }

    private function resolveAuditDetail(string $id, Professional $professional): CaseWorkspaceDetail
    {
        $detail = $this->resolveDetail($id, $professional);

        try {
            $this->authorise->require($detail->managedCase, $professional, CasePermission::ViewAudit);
        } catch (CaseAccessDenied $exception) {
            throw new ProfessionalCaseNotFoundHttpException(previous: $exception);
        }

        return $detail;
    }

    private function resolveExportDetail(string $id, Professional $professional): CaseWorkspaceDetail
    {
        $detail = $this->resolveDetail($id, $professional);

        try {
            $this->authorise->require($detail->managedCase, $professional, CasePermission::Export);
        } catch (CaseAccessDenied $exception) {
            throw new ProfessionalCaseNotFoundHttpException(previous: $exception);
        }

        return $detail;
    }

    /** @return array<string, mixed> */
    private function serializeSummary(CaseWorkspaceSummary $summary): array
    {
        $managedCase = $summary->managedCase;

        return [
            'id' => $managedCase->id()->toRfc4122(),
            'status' => $managedCase->status()->value,
            'modality' => $managedCase->modality()->value,
            'createdAt' => $managedCase->createdAt()->format(DATE_RFC3339_EXTENDED),
            'organisationName' => $managedCase->organisation()->name(),
            'assignmentRole' => $summary->assignment->role()->value,
            'pendingTasks' => $summary->pendingTasks,
            'overdueTasks' => $summary->overdueTasks,
            'nextDueAt' => $summary->nextDueAt?->format(DATE_RFC3339_EXTENDED),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeDetail(CaseWorkspaceDetail $detail, DateTimeImmutable $now): array
    {
        return [
            ...$this->serializeSummary($this->summaryFromDetail($detail, $now)),
            'permissions' => [
                'manage' => $detail->currentAssignment->permits(CasePermission::Manage),
                'manageAssignments' => $detail->currentAssignment->permits(CasePermission::ManageAssignments),
                'export' => $detail->currentAssignment->permits(CasePermission::Export),
                'viewAudit' => $detail->currentAssignment->permits(CasePermission::ViewAudit),
            ],
            'assignableProfessionals' => $detail->currentAssignment->permits(CasePermission::ManageAssignments)
                ? $this->serializeAssignableProfessionals($detail)
                : [],
            'people' => array_map($this->serializePerson(...), $detail->people),
            'assignments' => array_map($this->serializeAssignment(...), $detail->assignments),
            'tasks' => array_map(fn (CaseTask $task): array => $this->serializeTask($task, $now), $detail->tasks),
            'sourceReport' => $detail->sourceReport === null ? null : [
                'id' => $detail->sourceReport->id()->toRfc4122(),
                'publicReference' => $detail->sourceReport->publicReference(),
                'decision' => $detail->sourceDecision === null ? null : [
                    'outcome' => $detail->sourceDecision->outcome()->value,
                    'reason' => $detail->sourceDecision->reason()->toString(),
                    'decidedAt' => $detail->sourceDecision->decidedAt()->format(DATE_RFC3339_EXTENDED),
                ],
            ],
            'evidence' => array_map($this->serializeEvidence(...), $detail->evidence),
            'timeline' => $this->timeline($detail),
        ];
    }

    private function summaryFromDetail(CaseWorkspaceDetail $detail, DateTimeImmutable $now): CaseWorkspaceSummary
    {
        $pending = array_values(array_filter(
            $detail->tasks,
            static fn (CaseTask $task): bool => $task->status()->value === 'pending',
        ));

        return new CaseWorkspaceSummary(
            $detail->managedCase,
            $detail->currentAssignment,
            count($pending),
            count(array_filter($pending, static fn (CaseTask $task): bool => $task->isOverdue($now))),
            $pending === [] ? null : $pending[0]->dueAt(),
        );
    }

    /** @return array{id: string, name: string, role: string} */
    private function serializePerson(CaseInvolvedPerson $person): array
    {
        return ['id' => $person->id()->toRfc4122(), 'name' => $person->name()->toString(), 'role' => $person->role()->value];
    }

    /** @return array<string, mixed> */
    private function serializeAssignment(CaseAssignment $assignment): array
    {
        return [
            'id' => $assignment->id()->toRfc4122(),
            'professional' => [
                'id' => $assignment->professional()->id()->toRfc4122(),
                'name' => $assignment->professional()->name(),
            ],
            'role' => $assignment->role()->value,
            'assignedAt' => $assignment->assignedAt()->format(DATE_RFC3339_EXTENDED),
        ];
    }

    /** @return list<array{id: string, name: string}> */
    private function serializeAssignableProfessionals(CaseWorkspaceDetail $detail): array
    {
        $professionals = [];
        foreach ($this->memberships->findActiveByOrganisation($detail->managedCase->organisation()) as $membership) {
            $professional = $membership->professional();
            if ($professional->isActive()) {
                $professionals[$professional->id()->toRfc4122()] = [
                    'id' => $professional->id()->toRfc4122(),
                    'name' => $professional->name(),
                ];
            }
        }

        uasort($professionals, static fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']));

        return array_values($professionals);
    }

    /** @return array<string, mixed> */
    private function serializeTask(CaseTask $task, DateTimeImmutable $now): array
    {
        return [
            'id' => $task->id()->toRfc4122(),
            'title' => $task->title(),
            'stage' => $task->stage()->value,
            'kind' => $task->kind()->value,
            'status' => $task->status()->value,
            'dueAt' => $task->dueAt()->format(DATE_RFC3339_EXTENDED),
            'overdue' => $task->isOverdue($now),
            'owner' => ['id' => $task->owner()->id()->toRfc4122(), 'name' => $task->owner()->name()],
            'source' => [
                'id' => $task->source()->id()->toRfc4122(),
                'title' => $task->source()->title(),
                'version' => $task->source()->version(),
                'authority' => $task->source()->authority()->value,
                'territory' => $task->source()->territory(),
                'uri' => $task->source()->uri(),
            ],
            'resolvedAt' => $task->resolvedAt()?->format(DATE_RFC3339_EXTENDED),
            'resolvedBy' => $task->resolvedBy() === null ? null : [
                'id' => $task->resolvedBy()->id()->toRfc4122(),
                'name' => $task->resolvedBy()->name(),
            ],
            'notApplicableReason' => $task->notApplicableReason(),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeEvidence(ReportAttachment $attachment): array
    {
        return [
            'id' => $attachment->id()->toRfc4122(),
            'description' => $attachment->description()?->toString(),
            'mediaType' => $attachment->mediaType()->value,
            'byteSize' => $attachment->byteSize(),
            'createdAt' => $attachment->createdAt()->format(DATE_RFC3339_EXTENDED),
        ];
    }

    /** @return array{id: string, action: string, target: string, actorName: string, occurredAt: string} */
    private function serializeAuditEvent(CaseAuditEvent $event): array
    {
        return [
            'id' => $event->id()->toRfc4122(),
            'action' => $event->action()->value,
            'target' => $event->target()->value,
            'actorName' => $event->actor()->name(),
            'occurredAt' => $event->occurredAt()->format(DATE_RFC3339_EXTENDED),
        ];
    }

    /** @return list<array{type: string, occurredAt: string}> */
    private function timeline(CaseWorkspaceDetail $detail): array
    {
        $events = [[
            'type' => 'case_created',
            'occurredAt' => $detail->managedCase->createdAt()->format(DATE_RFC3339_EXTENDED),
        ]];

        foreach ($detail->assignments as $assignment) {
            $events[] = [
                'type' => 'assignment_added',
                'occurredAt' => $assignment->assignedAt()->format(DATE_RFC3339_EXTENDED),
            ];
        }
        foreach ($detail->tasks as $task) {
            $events[] = [
                'type' => 'task_created',
                'occurredAt' => $task->createdAt()->format(DATE_RFC3339_EXTENDED),
            ];
            if ($task->resolvedAt() !== null) {
                $events[] = [
                    'type' => 'task_resolved',
                    'occurredAt' => $task->resolvedAt()->format(DATE_RFC3339_EXTENDED),
                ];
            }
        }

        usort($events, static fn (array $left, array $right): int => $right['occurredAt'] <=> $left['occurredAt']);

        return $events;
    }

    /** @param array<string, mixed> $data */
    private function json(array $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse($data, $status, ['Cache-Control' => 'no-store']);
    }
}
