<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Application\SubmitAnonymousReport\SubmitAnonymousReport;
use App\Reporting\Application\SubmitAnonymousReport\SubmitAnonymousReportCommand;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportRepository;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use App\Reporting\Domain\ReporterAttentionCue;
use App\Reporting\Domain\ReportedPeople;
use App\Reporting\Domain\ReporterRecurrence;
use App\Reporting\Domain\ReporterTiming;
use App\Shared\Infrastructure\Idempotency\IdempotencyStore;
use App\Shared\Infrastructure\Logging\SecurityEventLogger;
use App\Shared\Presentation\Http\RateLimitEnforcer;
use InvalidArgumentException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use ValueError;

final readonly class SubmitAnonymousReportController
{
    private const IDEMPOTENCY_KEY_MAX_LENGTH = 200;

    public function __construct(
        private SubmitAnonymousReport $submitAnonymousReport,
        private ReportRepository $reportRepository,
        private RateLimitEnforcer $rateLimitEnforcer,
        private IdempotencyStore $idempotencyStore,
        private SecurityEventLogger $securityEventLogger,
        #[Autowire(service: 'limiter.report_submission')]
        private RateLimiterFactory $submissionLimiter,
    ) {
    }

    #[Route(
        '/api/v1/public/organisations/{publicReportingIdentifier}/reports',
        name: 'api_v1_public_submit_anonymous_report',
        methods: ['POST'],
        format: 'json',
    )]
    #[OA\Post(
        operationId: 'submitAnonymousReport',
        summary: 'Submit an anonymous report',
        description: 'Creates an anonymous report for the selected organisation. The access secret is returned only once and must be stored securely by the reporter.',
        security: [],
        tags: ['Public reporting'],
        parameters: [
            new OA\Parameter(
                name: 'publicReportingIdentifier',
                description: 'Public identifier of the organisation receiving the report.',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'string',
                    pattern: '^ORG_[0-9A-HJKMNP-TV-Z]{16}$',
                    example: 'ORG_EZ8E3G9CR2F7VGDF',
                ),
            ),
            new OA\Parameter(
                name: 'Idempotency-Key',
                description: 'Optional client-generated key. A repeated request with the same key and identifier returns the original report reference instead of creating a duplicate. The access secret is only ever returned on the first, original response — it is never replayed.',
                in: 'header',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    maxLength: 200,
                ),
            ),
        ],
        requestBody: new OA\RequestBody(
            description: 'Anonymous report information.',
            required: true,
            content: new OA\JsonContent(
                ref: new Model(
                    type: SubmitAnonymousReportRequest::class,
                ),
            ),
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'The anonymous report was created successfully.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/AnonymousReportSubmissionResponse',
                ),
            ),
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'A request with this Idempotency-Key and identifier was already processed. The report already exists; its access secret is not included, since it was only ever returned on the original response.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/IdempotentReportSubmissionResponse',
                ),
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'The request body contains malformed JSON.',
                content: new OA\MediaType(
                    mediaType: 'application/problem+json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/ProblemDetails',
                    ),
                ),
            ),
            new OA\Response(
                response: Response::HTTP_FORBIDDEN,
                description: 'The public fictional demonstration does not accept report content.',
                content: new OA\MediaType(
                    mediaType: 'application/problem+json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/ProblemDetails',
                    ),
                ),
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'The reporting organisation does not exist.',
                content: new OA\MediaType(
                    mediaType: 'application/problem+json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/ProblemDetails',
                    ),
                ),
            ),
            new OA\Response(
                response: Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                description: 'The request content type is not application/json.',
                content: new OA\MediaType(
                    mediaType: 'application/problem+json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/ProblemDetails',
                    ),
                ),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'The submitted report information is invalid.',
                content: new OA\MediaType(
                    mediaType: 'application/problem+json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/ProblemDetails',
                    ),
                ),
            ),
            new OA\Response(
                response: Response::HTTP_TOO_MANY_REQUESTS,
                description: 'Too many submissions were made.',
                content: new OA\MediaType(
                    mediaType: 'application/problem+json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/ProblemDetails',
                    ),
                ),
            ),
        ],
    )]
    public function __invoke(
        string $publicReportingIdentifier,
        Request $httpRequest,
        #[MapRequestPayload(
            acceptFormat: 'json',
            validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
        )]
        SubmitAnonymousReportRequest $payload,
    ): JsonResponse {
        // Checked before the rate limit deliberately: a replayed
        // response is cheap and shouldn't spend the caller's budget on
        // work that already happened.
        $idempotencyKey = $this->idempotencyKeyFrom($httpRequest);

        if ($idempotencyKey !== null) {
            $replay = $this->replayIfAlreadyProcessed(
                $publicReportingIdentifier,
                $idempotencyKey,
            );

            if ($replay !== null) {
                $this->securityEventLogger->idempotentReplay($httpRequest);

                return $replay;
            }
        }

        $this->rateLimitEnforcer->enforce(
            $this->submissionLimiter,
            'report_submission',
            $httpRequest,
        );

        try {
            $organisationIdentifier = PublicReportingIdentifier::fromString(
                $publicReportingIdentifier,
            );
        } catch (InvalidArgumentException $exception) {
            throw new ReportingOrganisationNotFoundHttpException(
                'Reporting organisation not found.',
                previous: $exception,
            );
        }

        try {
            $situationDescription = SituationDescription::fromString(
                $payload->situationDescription,
            );
            $situationContext = SituationContext::from(
                $payload->situationContext,
            );
            $reporterRecurrence = ReporterRecurrence::from(
                $payload->reporterRecurrence,
            );
            $reporterAttentionCue = ReporterAttentionCue::from(
                $payload->reporterAttentionCue,
            );
            $reporterTiming = ReporterTiming::from(
                $payload->reporterTiming,
            );
            $reportedPeople = ReportedPeople::fromNullableString(
                $payload->reportedPeople,
            );
        } catch (InvalidArgumentException|ValueError $exception) {
            throw new UnprocessableEntityHttpException(
                'The request contains invalid report information.',
                previous: $exception,
            );
        }

        $result = ($this->submitAnonymousReport)(
            new SubmitAnonymousReportCommand(
                $organisationIdentifier,
                $situationDescription,
                $situationContext,
                $reporterRecurrence,
                $reporterAttentionCue,
                $reporterTiming,
                $reportedPeople,
            ),
        );

        if ($idempotencyKey !== null) {
            $this->idempotencyStore->rememberRecordReference(
                $publicReportingIdentifier,
                $idempotencyKey,
                $result->publicReference,
            );
        }

        return new JsonResponse(
            [
                'publicReference' => $result->publicReference,
                'accessSecret' => $result->plainAccessSecret,
                'status' => $result->status->value,
                'createdAt' => $result->createdAt->format(
                    DATE_RFC3339_EXTENDED,
                ),
            ],
            Response::HTTP_CREATED,
            [
                'Cache-Control' => 'no-store',
            ],
        );
    }

    private function idempotencyKeyFrom(Request $request): ?string
    {
        $key = $request->headers->get('Idempotency-Key');

        if (
            $key === null
            || $key === ''
            || strlen($key) > self::IDEMPOTENCY_KEY_MAX_LENGTH
        ) {
            return null;
        }

        return $key;
    }

    private function replayIfAlreadyProcessed(
        string $publicReportingIdentifier,
        string $idempotencyKey,
    ): ?JsonResponse {
        $publicReference = $this->idempotencyStore->findRecordReference(
            $publicReportingIdentifier,
            $idempotencyKey,
        );

        if ($publicReference === null) {
            return null;
        }

        $report = $this->reportRepository->findByPublicReference(
            $publicReference,
        );

        if (!$report instanceof Report) {
            return null;
        }

        return new JsonResponse(
            [
                'publicReference' => $report->publicReference(),
                'status' => $report->status()->value,
                'createdAt' => $report->createdAt()->format(
                    DATE_RFC3339_EXTENDED,
                ),
            ],
            Response::HTTP_OK,
            [
                'Cache-Control' => 'no-store',
                'Idempotency-Replayed' => 'true',
            ],
        );
    }
}
