<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use App\Reporting\Application\VerifyReportAccess\VerifyReportAccess;
use App\Reporting\Application\VerifyReportAccess\VerifyReportAccessCommand;
use App\Shared\Presentation\Http\RateLimitEnforcer;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final readonly class VerifyReportAccessController
{
    public function __construct(
        private VerifyReportAccess $verifyReportAccess,
        private ReportAccessCookieFactory $cookieFactory,
        private RateLimitEnforcer $rateLimitEnforcer,
        #[Autowire(service: 'limiter.report_access_verification')]
        private RateLimiterFactory $verificationLimiter,
    ) {
    }

    #[Route(
        '/api/v1/public/report-access-grants',
        name: 'api_v1_public_verify_report_access',
        methods: ['POST'],
        format: 'json',
    )]
    #[OA\Post(
        operationId: 'verifyReportAccess',
        summary: 'Exchange an access secret for a report-scoped capability',
        description: 'Verifies a single-use anonymous access secret and, if accepted, issues a short-lived capability bound to exactly one report through a protected cookie. Malformed, unknown and revoked secrets return the same safe failure.',
        security: [],
        tags: ['Public reporting'],
        requestBody: new OA\RequestBody(
            description: 'The plain access secret shown once at submission time.',
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: VerifyReportAccessRequest::class),
            ),
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'A capability was issued and returned through a protected cookie.',
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: 'The access secret was not accepted.',
                content: new OA\MediaType(
                    mediaType: 'application/problem+json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/ProblemDetails',
                    ),
                ),
            ),
            new OA\Response(
                response: Response::HTTP_TOO_MANY_REQUESTS,
                description: 'Too many verification attempts were made.',
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
        Request $request,
        #[MapRequestPayload(acceptFormat: 'json')]
        VerifyReportAccessRequest $payload,
    ): Response {
        $this->rateLimitEnforcer->enforce(
            $this->verificationLimiter,
            'report_access_verification',
            $request,
        );

        $result = ($this->verifyReportAccess)(
            new VerifyReportAccessCommand(
                $payload->accessSecret,
                $this->cookieFactory->readFrom($request),
            ),
        );

        $response = new Response(
            null,
            Response::HTTP_NO_CONTENT,
            ['Cache-Control' => 'no-store'],
        );
        $response->headers->setCookie(
            $this->cookieFactory->issue(
                $result->plainCapabilityHandle,
                $result->absoluteExpiresAt,
            ),
        );

        return $response;
    }
}
