<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use App\Reporting\Application\GetReportFollowUpState\GetReportFollowUpState;
use App\Reporting\Domain\ReportFollowUpEntry;
use App\Shared\Presentation\Http\RateLimitEnforcer;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetReportFollowUpStateController
{
    public function __construct(
        private ReportAccessGuard $guard,
        private GetReportFollowUpState $getReportFollowUpState,
        private ReportAccessCookieFactory $cookieFactory,
        private RateLimitEnforcer $rateLimitEnforcer,
        #[Autowire(service: 'limiter.report_follow_up_read_ip')]
        private \Symfony\Component\RateLimiter\RateLimiterFactory $ipRateLimiter,
        #[Autowire(service: 'limiter.report_follow_up_read_capability')]
        private \Symfony\Component\RateLimiter\RateLimiterFactory $capabilityRateLimiter,
    ) {
    }

    #[Route(
        '/api/v1/reporter/report',
        name: 'api_v1_reporter_get_report_follow_up_state',
        methods: ['GET'],
    )]
    #[OA\Get(
        operationId: 'getReportFollowUpState',
        summary: "Read the current reporter's report and follow-up state",
        description: 'Returns the report resolved by the presented capability cookie: the original submission and any authorised follow-up entries. Excludes internal professional and case-management data by construction.',
        security: [['reportAccessCookie' => []]],
        tags: ['Public reporting'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'The report follow-up state.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ReportFollowUpStateResponse',
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
                response: Response::HTTP_TOO_MANY_REQUESTS,
                description: 'The follow-up read limit was exceeded.',
                content: new OA\MediaType(
                    mediaType: 'application/problem+json',
                    schema: new OA\Schema(ref: '#/components/schemas/ProblemDetails'),
                ),
            ),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $this->rateLimitEnforcer->enforce(
            $this->ipRateLimiter,
            'report_follow_up_read_ip',
            $request,
        );
        $this->rateLimitEnforcer->enforce(
            $this->capabilityRateLimiter,
            'report_follow_up_read_capability',
            $request,
            $this->cookieFactory->readFrom($request),
        );

        $grant = $this->guard->resolve($request);

        if ($grant === null) {
            throw new ReportAccessCapabilityRejectedHttpException();
        }

        $state = ($this->getReportFollowUpState)($grant->report());

        return new JsonResponse(
            [
                'publicReference' => $state->publicReference,
                'situationDescription' => $state->situationDescription,
                'situationContext' => $state->situationContext->value,
                'status' => $state->status->value,
                'createdAt' => $state->createdAt->format(
                    DATE_RFC3339_EXTENDED,
                ),
                'followUpEntries' => array_map(
                    $this->serializeFollowUpEntry(...),
                    $state->followUpEntries,
                ),
            ],
            Response::HTTP_OK,
            ['Cache-Control' => 'no-store'],
        );
    }

    /**
     * @return array{authorType: string, content: string, createdAt: string}
     */
    private function serializeFollowUpEntry(ReportFollowUpEntry $entry): array
    {
        return [
            'authorType' => $entry->authorType()->value,
            'content' => $entry->content()->toString(),
            'createdAt' => $entry->createdAt()->format(
                DATE_RFC3339_EXTENDED,
            ),
        ];
    }
}
