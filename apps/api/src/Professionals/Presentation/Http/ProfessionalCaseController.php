<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Cases\Application\CaseWorkspaceDetail;
use App\Cases\Application\CaseWorkspaceSummary;
use App\Cases\Application\ProfessionalCaseWorkspace;
use App\Cases\Domain\CaseAssignment;
use App\Cases\Domain\CaseInvolvedPerson;
use App\Cases\Domain\CasePermission;
use App\Cases\Domain\CaseTask;
use App\Professionals\Domain\Professional;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Presentation\Http\PrivateReportAttachmentDownloadResponder;
use App\Reporting\Presentation\Http\ReportAttachmentUnavailableHttpException;
use App\Shared\Infrastructure\Logging\SecurityEventLogger;
use App\Shared\Presentation\Http\RateLimitEnforcer;
use DateTimeImmutable;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final readonly class ProfessionalCaseController
{
    public function __construct(
        private ProfessionalCaseWorkspace $workspace,
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
        summary: 'List cases assigned to the current professional',
        security: [['professionalSession' => []]],
        tags: ['Professional cases'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'The bounded case workspace overview.',
                content: new OA\JsonContent(ref: '#/components/schemas/ProfessionalCasePage'),
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
        ],
    )]
    public function list(#[CurrentUser] Professional $professional): JsonResponse
    {
        $now = DateTimeImmutable::createFromTimestamp(microtime(true));

        return $this->json([
            'items' => array_map(
                fn (CaseWorkspaceSummary $summary): array => $this->serializeSummary($summary),
                $this->workspace->list($professional, $now),
            ),
        ]);
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

        return $response;
    }

    private function resolveDetail(string $id, Professional $professional): CaseWorkspaceDetail
    {
        if (!Uuid::isValid($id)) {
            throw new ProfessionalCaseNotFoundHttpException();
        }

        return $this->workspace->detail(Uuid::fromString($id), $professional)
            ?? throw new ProfessionalCaseNotFoundHttpException();
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
            ],
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
    private function json(array $data): JsonResponse
    {
        return new JsonResponse($data, Response::HTTP_OK, ['Cache-Control' => 'no-store']);
    }
}
