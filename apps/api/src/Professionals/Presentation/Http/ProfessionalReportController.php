<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Professionals\Application\AuthorisedReportingOrganisations;
use App\Professionals\Domain\Professional;
use App\Reporting\Application\AddProfessionalReportResponse\AddProfessionalReportResponse;
use App\Reporting\Application\ProfessionalInbox\ProfessionalReportDetail;
use App\Reporting\Application\ProfessionalInbox\ProfessionalReportInbox;
use App\Reporting\Application\TriageReport\TriageReport;
use App\Reporting\Domain\FollowUpEntryContent;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ProfessionalConcernCategory;
use App\Reporting\Domain\ReporterAttentionCue;
use App\Reporting\Domain\ReporterRecurrence;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportAttachmentRepository;
use App\Reporting\Domain\ReportAlreadyReviewed;
use App\Reporting\Domain\ReportFollowUpEntry;
use App\Reporting\Domain\ReportReviewReason;
use App\Reporting\Domain\ReportStatus;
use App\Reporting\Domain\ReportTriageAlreadyFinalised;
use App\Reporting\Domain\ReportTriageDecision;
use App\Reporting\Domain\ReportTriageOutcome;
use App\Reporting\Domain\ReportTriageReason;
use App\Reporting\Domain\ReportMustBeReviewedBeforeTriage;
use App\Reporting\Presentation\Http\PrivateReportAttachmentDownloadResponder;
use App\Reporting\Presentation\Http\ReportAttachmentPresenter;
use App\Reporting\Presentation\Http\ReportAttachmentUnavailableHttpException;
use App\Shared\Infrastructure\Logging\SecurityEventLogger;
use App\Shared\Presentation\Http\RateLimitEnforcer;
use Doctrine\ORM\OptimisticLockException;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;
use ValueError;

final readonly class ProfessionalReportController
{
    private const DEFAULT_LIMIT = 20;
    private const MAXIMUM_LIMIT = 50;

    public function __construct(
        private AuthorisedReportingOrganisations $authorisedOrganisations,
        private ProfessionalReportInbox $inbox,
        private TriageReport $triageReport,
        private ProfessionalReportCursorCodec $cursorCodec,
        private AddProfessionalReportResponse $addProfessionalResponse,
        private ReportAttachmentRepository $attachments,
        private ReportAttachmentPresenter $attachmentPresenter,
        private PrivateReportAttachmentDownloadResponder $attachmentDownloadResponder,
        private RateLimitEnforcer $rateLimitEnforcer,
        private SecurityEventLogger $securityEventLogger,
        #[Autowire(service: 'limiter.professional_attachment_download_ip')]
        private RateLimiterFactory $attachmentDownloadRateLimiter,
    ) {
    }

    #[Route('/api/v1/professional/reports', name: 'api_v1_professional_list_reports', methods: ['GET'])]
    #[OA\Get(
        operationId: 'listProfessionalReports',
        summary: 'List reports in the professional inbox',
        security: [['professionalSession' => []]],
        tags: ['Professional reports'],
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                schema: new OA\Schema(type: 'string', enum: ['new', 'reviewed']),
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                schema: new OA\Schema(type: 'integer', maximum: self::MAXIMUM_LIMIT, minimum: 1),
            ),
            new OA\Parameter(
                name: 'cursor',
                in: 'query',
                schema: new OA\Schema(type: 'string', maxLength: 512),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'An organisation-scoped report page.',
                content: new OA\JsonContent(ref: '#/components/schemas/ProfessionalReportPage'),
            ),
            new OA\Response(response: 400, description: 'A filter or cursor is invalid.'),
            new OA\Response(response: 401, description: 'A professional session is required.'),
        ],
    )]
    public function list(
        #[CurrentUser] Professional $professional,
        Request $request,
    ): JsonResponse {
        $status = $this->parseStatus($request->query->getString('status'));
        $limit = $this->parseLimit($request->query->get('limit'));
        $cursorValue = $request->query->getString('cursor');
        $cursor = $cursorValue === '' ? null : $this->cursorCodec->decode($cursorValue);

        if ($cursorValue !== '' && $cursor === null) {
            throw new BadRequestHttpException('The report cursor is invalid.');
        }

        $organisations = ($this->authorisedOrganisations)($professional);

        if ($organisations === []) {
            return $this->json([
                'items' => [],
                'pagination' => ['limit' => $limit, 'nextCursor' => null],
            ]);
        }

        $page = $this->inbox->list($organisations, $status, $cursor, $limit);

        return $this->json([
            'items' => array_map($this->serializeSummary(...), $page->items),
            'pagination' => [
                'limit' => $limit,
                'nextCursor' => $page->nextCursor === null
                    ? null
                    : $this->cursorCodec->encode($page->nextCursor),
            ],
        ]);
    }

    #[Route(
        '/api/v1/professional/reports/{id}',
        name: 'api_v1_professional_get_report',
        methods: ['GET'],
    )]
    #[OA\Get(
        operationId: 'getProfessionalReport',
        summary: 'Read an authorised professional report',
        security: [['professionalSession' => []]],
        tags: ['Professional reports'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The authorised report detail.',
                content: new OA\JsonContent(ref: '#/components/schemas/ProfessionalReportDetail'),
            ),
            new OA\Response(response: 401, description: 'A professional session is required.'),
            new OA\Response(response: 404, description: 'The report is unavailable in this scope.'),
        ],
    )]
    public function detail(
        string $id,
        #[CurrentUser] Professional $professional,
    ): JsonResponse {
        $detail = $this->resolveDetail($id, $professional);

        return $this->json($this->serializeDetail($detail));
    }

    #[Route(
        '/api/v1/professional/reports/{id}/attachments',
        name: 'api_v1_professional_list_report_attachments',
        methods: ['GET'],
    )]
    #[OA\Get(
        operationId: 'listProfessionalReportAttachments',
        summary: 'List available evidence for an authorised professional report',
        description: 'Returns only available attachments in the professional\'s active triage scope. Processing, rejected and deleted evidence is omitted.',
        security: [['professionalSession' => []]],
        tags: ['Professional reports'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Available attachments in the authorised report scope.',
                content: new OA\JsonContent(
                    type: 'object',
                    required: ['items'],
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ProfessionalReportAttachment'),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'A professional session is required.'),
            new OA\Response(response: 404, description: 'The report is unavailable in this scope.'),
        ],
    )]
    public function listAttachments(
        string $id,
        #[CurrentUser] Professional $professional,
    ): JsonResponse {
        $detail = $this->resolveDetail($id, $professional);
        $attachments = array_values(array_filter(
            $this->attachments->findByReport($detail->report),
            static fn (ReportAttachment $attachment): bool => $attachment->isAvailable(),
        ));

        return $this->json([
            'items' => array_map($this->attachmentPresenter->professional(...), $attachments),
        ]);
    }

    #[Route(
        '/api/v1/professional/reports/{id}/attachments/{attachmentId}/download',
        name: 'api_v1_professional_download_report_attachment',
        methods: ['GET'],
    )]
    #[OA\Get(
        operationId: 'downloadProfessionalReportAttachment',
        summary: 'Download available evidence in an authorised professional report',
        description: 'Streams one available private attachment with a generated filename. It never returns a direct object URL or an inline preview.',
        security: [['professionalSession' => []]],
        tags: ['Professional reports'],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'The authorised attachment stream.'),
            new OA\Response(response: 401, description: 'A professional session is required.'),
            new OA\Response(response: 404, description: 'The report or attachment is unavailable in this scope.'),
            new OA\Response(response: 429, description: 'The download rate or concurrent stream limit was exceeded.'),
        ],
    )]
    public function downloadAttachment(
        string $id,
        string $attachmentId,
        #[CurrentUser] Professional $professional,
        Request $request,
    ): StreamedResponse {
        $this->rateLimitEnforcer->enforce(
            $this->attachmentDownloadRateLimiter,
            'professional_attachment_download_ip',
            $request,
        );
        $detail = $this->resolveDetail($id, $professional);

        if (!Uuid::isValid($attachmentId)) {
            $this->securityEventLogger->professionalAttachmentDownloadDenied($request);

            throw new ReportAttachmentUnavailableHttpException();
        }

        $attachment = $this->attachments->findByIdForReport(
            Uuid::fromString($attachmentId),
            $detail->report,
        );

        if ($attachment === null || !$attachment->isAvailable()) {
            $this->securityEventLogger->professionalAttachmentDownloadDenied($request);

            throw new ReportAttachmentUnavailableHttpException();
        }

        try {
            $response = $this->attachmentDownloadResponder->respond($attachment);
        } catch (ReportAttachmentUnavailableHttpException $exception) {
            $this->securityEventLogger->professionalAttachmentDownloadDenied($request);

            throw $exception;
        }

        $this->securityEventLogger->professionalAttachmentDownloaded($request);

        return $response;
    }

    #[Route(
        '/api/v1/professional/reports/{id}/reviews',
        name: 'api_v1_professional_review_report',
        methods: ['POST'],
        format: 'json',
    )]
    #[OA\Post(
        operationId: 'reviewProfessionalReport',
        summary: 'Record the initial professional review of a report',
        security: [['professionalSession' => []]],
        tags: ['Professional reports'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reason'],
                properties: [
                    new OA\Property(
                        property: 'reason',
                        type: 'string',
                        minLength: ReportReviewReason::MIN_LENGTH,
                        maxLength: ReportReviewReason::MAX_LENGTH,
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'The initial review was recorded.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ProfessionalReportReviewResponse',
                ),
            ),
            new OA\Response(response: 401, description: 'A professional session is required.'),
            new OA\Response(response: 404, description: 'The report is unavailable in this scope.'),
            new OA\Response(response: 409, description: 'The report was already reviewed.'),
            new OA\Response(response: 422, description: 'The review reason is invalid.'),
        ],
    )]
    public function review(
        string $id,
        #[CurrentUser] Professional $professional,
        #[MapRequestPayload(acceptFormat: 'json')]
        ReviewProfessionalReportRequest $payload,
    ): JsonResponse {
        if (!Uuid::isValid($id)) {
            throw new ProfessionalReportNotFoundHttpException();
        }

        try {
            $reason = ReportReviewReason::fromString($payload->reason);
            $professionalConcernCategory = ProfessionalConcernCategory::from($payload->professionalConcernCategory);
            $professionalRecurrence = ReporterRecurrence::from($payload->professionalRecurrence);
            $professionalAttentionCue = ReporterAttentionCue::from($payload->professionalAttentionCue);
        } catch (InvalidArgumentException|ValueError $exception) {
            throw new InvalidProfessionalReportRequestHttpException(previous: $exception);
        }

        $organisations = ($this->authorisedOrganisations)($professional);

        if ($organisations === []) {
            throw new ProfessionalReportNotFoundHttpException();
        }

        try {
            $report = $this->inbox->review(
                Uuid::fromString($id),
                $organisations,
                $reason,
                $professional->id(),
                $professionalConcernCategory,
                $professionalRecurrence,
                $professionalAttentionCue,
            );
        } catch (ReportAlreadyReviewed|OptimisticLockException $exception) {
            throw new ProfessionalReportAlreadyReviewedHttpException(previous: $exception);
        }

        if ($report === null) {
            throw new ProfessionalReportNotFoundHttpException();
        }

        return $this->json(
            ['review' => $this->serializeReview($report)],
            Response::HTTP_CREATED,
        );
    }

    #[Route(
        '/api/v1/professional/reports/{id}/triage-decisions',
        name: 'api_v1_professional_triage_report',
        methods: ['POST'],
        format: 'json',
    )]
    #[OA\Post(
        operationId: 'triageProfessionalReport',
        summary: 'Record an authorised report triage decision',
        description: 'Appends a keep decision or records one terminal redirect, dismissal or idempotent report-to-case link. The original report is never rewritten or deleted.',
        security: [['professionalSession' => []]],
        tags: ['Professional reports'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['outcome', 'reason'],
                additionalProperties: false,
                properties: [
                    new OA\Property(
                        property: 'outcome',
                        type: 'string',
                        enum: ['keep', 'redirect', 'dismiss', 'link_to_case'],
                    ),
                    new OA\Property(
                        property: 'reason',
                        type: 'string',
                        minLength: ReportTriageReason::MIN_LENGTH,
                        maxLength: ReportTriageReason::MAX_LENGTH,
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'The decision was recorded, or the existing case link was returned.',
                content: new OA\JsonContent(ref: '#/components/schemas/ProfessionalReportTriageResponse'),
            ),
            new OA\Response(response: 401, description: 'A professional session is required.'),
            new OA\Response(response: 404, description: 'The report is unavailable in this scope.'),
            new OA\Response(response: 409, description: 'The report is not reviewed or triage is already final.'),
            new OA\Response(response: 422, description: 'The triage decision is invalid.'),
        ],
    )]
    public function triage(
        string $id,
        #[CurrentUser] Professional $professional,
        #[MapRequestPayload(acceptFormat: 'json')]
        TriageProfessionalReportRequest $payload,
    ): JsonResponse {
        $detail = $this->resolveDetail($id, $professional);

        try {
            $outcome = ReportTriageOutcome::from($payload->outcome);
            $reason = ReportTriageReason::fromString($payload->reason);
        } catch (InvalidArgumentException|\ValueError $exception) {
            throw new InvalidProfessionalReportTriageHttpException(previous: $exception);
        }

        try {
            $decision = $this->triageReport->decide(
                $detail->report,
                $professional,
                $outcome,
                $reason,
            );
        } catch (ReportMustBeReviewedBeforeTriage|ReportTriageAlreadyFinalised $exception) {
            throw new ProfessionalReportTriageConflictHttpException(previous: $exception);
        }

        return $this->json(
            ['decision' => $this->serializeTriageDecision($decision)],
            Response::HTTP_CREATED,
        );
    }

    #[Route(
        '/api/v1/professional/reports/{id}/responses',
        name: 'api_v1_professional_respond_to_report',
        methods: ['POST'],
        format: 'json',
    )]
    #[OA\Post(
        operationId: 'respondToProfessionalReport',
        summary: 'Publish a reporter-visible professional response',
        description: 'Appends an immutable professional-authored entry to the reporter-visible conversation. Internal notes are not accepted by this resource.',
        security: [['professionalSession' => []]],
        tags: ['Professional reports'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                additionalProperties: false,
                properties: [
                    new OA\Property(
                        property: 'content',
                        type: 'string',
                        maxLength: FollowUpEntryContent::MAX_LENGTH,
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'The reporter-visible response was published.',
                content: new OA\JsonContent(ref: '#/components/schemas/FollowUpEntry'),
            ),
            new OA\Response(response: 401, description: 'A professional session is required.'),
            new OA\Response(response: 404, description: 'The report is unavailable in this scope.'),
            new OA\Response(response: 409, description: 'The conversation has reached its entry limit.'),
            new OA\Response(response: 422, description: 'The response content is invalid.'),
        ],
    )]
    public function respond(
        string $id,
        #[CurrentUser] Professional $professional,
        #[MapRequestPayload(acceptFormat: 'json')]
        RespondToProfessionalReportRequest $payload,
    ): JsonResponse {
        $detail = $this->resolveDetail($id, $professional);

        try {
            $content = FollowUpEntryContent::fromString($payload->content);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidProfessionalReportResponseHttpException(previous: $exception);
        }

        $entry = ($this->addProfessionalResponse)(
            $detail->report,
            $professional->id(),
            $content,
        );

        return $this->json(
            $this->serializeFollowUpEntry($entry),
            Response::HTTP_CREATED,
        );
    }

    private function resolveDetail(string $id, Professional $professional): ProfessionalReportDetail
    {
        if (!Uuid::isValid($id)) {
            throw new ProfessionalReportNotFoundHttpException();
        }

        $organisations = ($this->authorisedOrganisations)($professional);

        if ($organisations === []) {
            throw new ProfessionalReportNotFoundHttpException();
        }

        return $this->inbox->detail(Uuid::fromString($id), $organisations)
            ?? throw new ProfessionalReportNotFoundHttpException();
    }

    /** @return array<string, mixed> */
    private function serializeSummary(Report $report): array
    {
        return [
            'id' => $report->id()->toRfc4122(),
            'publicReference' => $report->publicReference(),
            'situationPreview' => $this->situationPreview(
                $report->situationDescription()->toString(),
            ),
            'situationContext' => $report->situationContext()->value,
            'status' => $this->professionalStatus($report->status()),
            'createdAt' => $report->createdAt()->format(DATE_RFC3339_EXTENDED),
        ];
    }

    private function situationPreview(string $description): string
    {
        $preview = grapheme_substr($description, 0, 110);

        if ($preview === false || grapheme_strlen($description) <= 110) {
            return $description;
        }

        return rtrim($preview).'…';
    }

    /** @return array<string, mixed> */
    private function serializeDetail(ProfessionalReportDetail $detail): array
    {
        $report = $detail->report;

        return [
            ...$this->serializeSummary($report),
            'situationDescription' => $report->situationDescription()->toString(),
            'review' => $report->status() === ReportStatus::Reviewed
                ? $this->serializeReview($report)
                : null,
            'followUpEntries' => array_map(
                $this->serializeFollowUpEntry(...),
                $detail->followUpEntries,
            ),
            'triageDecisions' => array_map(
                $this->serializeTriageDecision(...),
                $this->triageReport->history($report),
            ),
        ];
    }

    /** @return array{id: string, outcome: string, reason: string, decidedAt: string, decidedBy: array{id: string, name: string}, caseId: ?string} */
    private function serializeTriageDecision(ReportTriageDecision $decision): array
    {
        return [
            'id' => $decision->id()->toRfc4122(),
            'outcome' => $decision->outcome()->value,
            'reason' => $decision->reason()->toString(),
            'decidedAt' => $decision->decidedAt()->format(DATE_RFC3339_EXTENDED),
            'decidedBy' => [
                'id' => $decision->decidedBy()->id()->toRfc4122(),
                'name' => $decision->decidedBy()->name(),
            ],
            'caseId' => $decision->managedCase()?->id()->toRfc4122(),
        ];
    }

    /** @return array{reason: string, reviewedAt: string} */
    private function serializeReview(Report $report): array
    {
        $reason = $report->reviewReason();
        $reviewedAt = $report->reviewedAt();

        if ($reason === null || $reviewedAt === null) {
            throw new \LogicException('A reviewed report must contain complete review metadata.');
        }

        return [
            'reason' => $reason->toString(),
            'reviewedAt' => $reviewedAt->format(DATE_RFC3339_EXTENDED),
            'professionalTaxonomy' => [
                'version' => $report->taxonomyVersion(),
                'concernCategory' => $report->professionalConcernCategory()?->value,
                'recurrence' => $report->professionalRecurrence()?->value,
                'attentionCue' => $report->professionalAttentionCue()?->value,
            ],
        ];
    }

    /** @return array{authorType: string, content: string, createdAt: string} */
    private function serializeFollowUpEntry(ReportFollowUpEntry $entry): array
    {
        return [
            'authorType' => $entry->authorType()->value,
            'content' => $entry->content()->toString(),
            'createdAt' => $entry->createdAt()->format(DATE_RFC3339_EXTENDED),
        ];
    }

    private function professionalStatus(ReportStatus $status): string
    {
        return $status === ReportStatus::Received ? 'new' : 'reviewed';
    }

    private function parseStatus(string $status): ?ReportStatus
    {
        return match ($status) {
            '' => null,
            'new' => ReportStatus::Received,
            'reviewed' => ReportStatus::Reviewed,
            default => throw new BadRequestHttpException('The report status filter is invalid.'),
        };
    }

    private function parseLimit(mixed $limit): int
    {
        if ($limit === null || $limit === '') {
            return self::DEFAULT_LIMIT;
        }

        if (!is_string($limit) || !ctype_digit($limit)) {
            throw new BadRequestHttpException('The report page limit is invalid.');
        }

        $parsed = (int) $limit;

        if ($parsed < 1 || $parsed > self::MAXIMUM_LIMIT) {
            throw new BadRequestHttpException('The report page limit is invalid.');
        }

        return $parsed;
    }

    /** @param array<string, mixed> $data */
    private function json(array $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse($data, $status, ['Cache-Control' => 'no-store']);
    }
}
