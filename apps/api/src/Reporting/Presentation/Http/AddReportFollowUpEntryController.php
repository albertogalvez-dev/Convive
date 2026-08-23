<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use App\Reporting\Application\AddReportFollowUpEntry\AddReportFollowUpEntry;
use App\Reporting\Domain\FollowUpEntryContent;
use App\Shared\Presentation\Http\RateLimitEnforcer;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AddReportFollowUpEntryController
{
    public function __construct(
        private ReportAccessGuard $guard,
        private AddReportFollowUpEntry $addReportFollowUpEntry,
        private ReportAccessCookieFactory $cookieFactory,
        private RateLimitEnforcer $rateLimitEnforcer,
        #[Autowire(service: 'limiter.report_follow_up_append_ip')]
        private \Symfony\Component\RateLimiter\RateLimiterFactory $ipRateLimiter,
        #[Autowire(service: 'limiter.report_follow_up_append_capability')]
        private \Symfony\Component\RateLimiter\RateLimiterFactory $capabilityRateLimiter,
    ) {
    }

    #[Route(
        '/api/v1/reporter/report/follow-up-entries',
        name: 'api_v1_reporter_add_report_follow_up_entry',
        methods: ['POST'],
        format: 'json',
    )]
    #[OA\Post(
        operationId: 'addReportFollowUpEntry',
        summary: "Append a follow-up entry to the reporter's own report",
        description: 'Adds reporter-authored text to the report resolved by the presented capability cookie. The original submission remains unchanged; entries are ordered and cannot be edited or removed.',
        security: [['reportAccessCookie' => []]],
        tags: ['Public reporting'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                additionalProperties: false,
                required: ['content'],
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
                description: 'The follow-up entry was added.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FollowUpEntry',
                ),
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: 'The report access capability was not accepted.',
                content: new OA\MediaType(
                    mediaType: 'application/problem+json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/ProblemDetails',
                    ),
                ),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'The submitted follow-up entry is invalid.',
                content: new OA\MediaType(
                    mediaType: 'application/problem+json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/ProblemDetails',
                    ),
                ),
            ),
            new OA\Response(
                response: Response::HTTP_CONFLICT,
                description: 'The report cannot accept more follow-up entries.',
                content: new OA\MediaType(
                    mediaType: 'application/problem+json',
                    schema: new OA\Schema(ref: '#/components/schemas/ProblemDetails'),
                ),
            ),
            new OA\Response(
                response: Response::HTTP_TOO_MANY_REQUESTS,
                description: 'The follow-up append limit was exceeded.',
                content: new OA\MediaType(
                    mediaType: 'application/problem+json',
                    schema: new OA\Schema(ref: '#/components/schemas/ProblemDetails'),
                ),
            ),
        ],
    )]
    public function __invoke(
        Request $request,
        #[MapRequestPayload(acceptFormat: 'json')]
        AddReportFollowUpEntryRequest $payload,
    ): JsonResponse {
        $this->rateLimitEnforcer->enforce(
            $this->ipRateLimiter,
            'report_follow_up_append_ip',
            $request,
        );
        $this->rateLimitEnforcer->enforce(
            $this->capabilityRateLimiter,
            'report_follow_up_append_capability',
            $request,
            $this->cookieFactory->readFrom($request),
        );

        $grant = $this->guard->resolve($request);

        if ($grant === null) {
            throw new ReportAccessCapabilityRejectedHttpException();
        }

        try {
            $content = FollowUpEntryContent::fromString($payload->content);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidFollowUpEntryHttpException($exception);
        }

        $entry = ($this->addReportFollowUpEntry)(
            $grant->report(),
            $content,
        );

        return new JsonResponse(
            [
                'authorType' => $entry->authorType()->value,
                'content' => $entry->content()->toString(),
                'createdAt' => $entry->createdAt()->format(
                    DATE_RFC3339_EXTENDED,
                ),
            ],
            Response::HTTP_CREATED,
            ['Cache-Control' => 'no-store'],
        );
    }
}
