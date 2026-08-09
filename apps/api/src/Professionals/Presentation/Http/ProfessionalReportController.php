<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Professionals\Application\AuthorisedReportingOrganisations;
use App\Professionals\Domain\Professional;
use App\Reporting\Application\ProfessionalInbox\ProfessionalReportDetail;
use App\Reporting\Application\ProfessionalInbox\ProfessionalReportInbox;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAlreadyReviewed;
use App\Reporting\Domain\ReportFollowUpEntry;
use App\Reporting\Domain\ReportReviewReason;
use App\Reporting\Domain\ReportStatus;
use Doctrine\ORM\OptimisticLockException;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final readonly class ProfessionalReportController
{
    private const DEFAULT_LIMIT = 20;
    private const MAXIMUM_LIMIT = 50;

    public function __construct(
        private AuthorisedReportingOrganisations $authorisedOrganisations,
        private ProfessionalReportInbox $inbox,
        private ProfessionalReportCursorCodec $cursorCodec,
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
        } catch (InvalidArgumentException $exception) {
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
