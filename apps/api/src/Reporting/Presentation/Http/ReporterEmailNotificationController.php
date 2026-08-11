<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use App\Reporting\Domain\ReporterEmailAddress;
use App\Reporting\Infrastructure\DoctrineReporterEmailNotifications;
use App\Shared\Presentation\Http\RateLimitEnforcer;
use App\Shared\Infrastructure\Logging\SecurityEventLogger;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final readonly class ReporterEmailNotificationController
{
    private const CONSENT_NOTICE_VERSION = 'reporter-email-v1';
    private const CSRF_TOKEN_ID = 'reporter_email_configuration';

    public function __construct(
        private ReportAccessGuard $guard,
        private ReportAccessCookieFactory $cookieFactory,
        private DoctrineReporterEmailNotifications $notifications,
        private RateLimitEnforcer $rateLimitEnforcer,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private SecurityEventLogger $securityEventLogger,
        #[Autowire(param: 'reporter_email.enabled')]
        private bool $enabled,
        #[Autowire(service: 'limiter.reporter_email_configuration_ip')]
        private RateLimiterFactory $configurationIpLimiter,
        #[Autowire(service: 'limiter.reporter_email_configuration_capability')]
        private RateLimiterFactory $configurationCapabilityLimiter,
        #[Autowire(service: 'limiter.reporter_email_verification')]
        private RateLimiterFactory $verificationLimiter,
    ) {
    }

    #[Route('/api/v1/reporter/report/email-notifications', methods: ['GET'])]
    #[OA\Get(
        operationId: 'getReporterEmailNotificationStatus',
        summary: 'Read the optional reporter-email notification state',
        description: 'Returns only whether the feature is available and whether the contact is absent, pending or verified. It never returns the address.',
        security: [['reportAccessCookie' => []]],
        tags: ['Public reporting'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'The privacy-minimised notification state.',
                content: new OA\JsonContent(
                    required: ['enabled', 'status'],
                    properties: [
                        new OA\Property(property: 'enabled', type: 'boolean'),
                        new OA\Property(property: 'status', type: 'string', enum: ['none', 'pending', 'verified']),
                    ],
                ),
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: 'The report access capability was not accepted.',
                content: new OA\MediaType(mediaType: 'application/problem+json', schema: new OA\Schema(ref: '#/components/schemas/ProblemDetails')),
            ),
        ],
    )]
    public function status(Request $request): JsonResponse
    {
        $report = $this->authorisedReport($request);

        return $this->json(['enabled' => $this->enabled, 'status' => $this->notifications->status($report)]);
    }

    #[Route('/api/v1/reporter/report/email-notifications', methods: ['PUT'], format: 'json')]
    #[OA\Put(
        operationId: 'configureReporterEmailNotifications',
        summary: 'Request optional generic reporter-email notifications',
        description: 'Stores a separate pending contact and queues a generic mailbox-verification message. The address grants no report access.',
        security: [['reportAccessCookie' => []]],
        tags: ['Public reporting'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'consentAccepted'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: ReporterEmailAddress::MAX_LENGTH),
                    new OA\Property(property: 'consentAccepted', type: 'boolean', enum: [true]),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_ACCEPTED,
                description: 'Verification was queued or the existing verified contact was retained.',
                content: new OA\JsonContent(
                    required: ['enabled', 'status'],
                    properties: [
                        new OA\Property(property: 'enabled', type: 'boolean'),
                        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'verified']),
                    ],
                ),
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'The report access capability was not accepted.', content: new OA\MediaType(mediaType: 'application/problem+json', schema: new OA\Schema(ref: '#/components/schemas/ProblemDetails'))),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'The request failed same-origin validation.', content: new OA\MediaType(mediaType: 'application/problem+json', schema: new OA\Schema(ref: '#/components/schemas/ProblemDetails'))),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The address or consent evidence is invalid.', content: new OA\MediaType(mediaType: 'application/problem+json', schema: new OA\Schema(ref: '#/components/schemas/ProblemDetails'))),
            new OA\Response(response: Response::HTTP_TOO_MANY_REQUESTS, description: 'The configuration limit was exceeded.', content: new OA\MediaType(mediaType: 'application/problem+json', schema: new OA\Schema(ref: '#/components/schemas/ProblemDetails'))),
        ],
    )]
    public function configure(
        Request $request,
        #[MapRequestPayload(acceptFormat: 'json')]
        ConfigureReporterEmailRequest $payload,
    ): JsonResponse {
        $this->requireEnabled();
        $this->assertSameOrigin($request);
        $report = $this->authorisedReport($request);
        $this->rateLimitEnforcer->enforce($this->configurationIpLimiter, 'reporter_email_configuration_ip', $request);
        $this->rateLimitEnforcer->enforce(
            $this->configurationCapabilityLimiter,
            'reporter_email_configuration_capability',
            $request,
            $this->capability($request),
        );

        try {
            $email = ReporterEmailAddress::fromString($payload->email);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidReporterEmailHttpException(previous: $exception);
        }

        $status = $this->notifications->configure($report, $email, self::CONSENT_NOTICE_VERSION);

        return $this->json(['enabled' => true, 'status' => $status], Response::HTTP_ACCEPTED);
    }

    #[Route('/api/v1/reporter/report/email-notifications', methods: ['DELETE'])]
    #[OA\Delete(
        operationId: 'removeReporterEmailNotifications',
        summary: 'Remove the optional reporter-email contact',
        description: 'Immediately deletes the separated contact and cascades all queued or retrying delivery work.',
        security: [['reportAccessCookie' => []]],
        tags: ['Public reporting'],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'The contact and notification work were removed.'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'The report access capability was not accepted.', content: new OA\MediaType(mediaType: 'application/problem+json', schema: new OA\Schema(ref: '#/components/schemas/ProblemDetails'))),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'The request failed same-origin validation.', content: new OA\MediaType(mediaType: 'application/problem+json', schema: new OA\Schema(ref: '#/components/schemas/ProblemDetails'))),
        ],
    )]
    public function remove(Request $request): Response
    {
        $this->requireEnabled();
        $this->assertSameOrigin($request);
        $report = $this->authorisedReport($request);
        $this->notifications->remove($report);

        return new Response(status: Response::HTTP_NO_CONTENT, headers: ['Cache-Control' => 'no-store']);
    }

    #[Route('/api/v1/public/reporter-email-verifications', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'verifyReporterEmail',
        summary: 'Verify an optional reporter-email contact',
        description: 'Consumes a single-use mailbox token. It grants no report capability and returns no report information.',
        tags: ['Public reporting'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', pattern: '^[0-9a-f]{64}$'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'The mailbox was verified.',
                content: new OA\JsonContent(
                    required: ['verified'],
                    properties: [new OA\Property(property: 'verified', type: 'boolean', enum: [true])],
                ),
            ),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The token is invalid, expired or already used.', content: new OA\JsonContent(required: ['verified'], properties: [new OA\Property(property: 'verified', type: 'boolean', enum: [false])])),
            new OA\Response(response: Response::HTTP_TOO_MANY_REQUESTS, description: 'The verification limit was exceeded.', content: new OA\MediaType(mediaType: 'application/problem+json', schema: new OA\Schema(ref: '#/components/schemas/ProblemDetails'))),
        ],
    )]
    public function verify(
        Request $request,
        #[MapRequestPayload(acceptFormat: 'json')]
        VerifyReporterEmailRequest $payload,
    ): JsonResponse {
        $this->requireEnabled();
        $this->rateLimitEnforcer->enforce($this->verificationLimiter, 'reporter_email_verification', $request);
        $verified = $this->notifications->verify($payload->token);

        return $this->json(['verified' => $verified], $verified ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function authorisedReport(Request $request): \App\Reporting\Domain\Report
    {
        $grant = $this->guard->resolve($request);

        if ($grant === null) {
            throw new ReportAccessCapabilityRejectedHttpException();
        }

        return $grant->report();
    }

    private function capability(Request $request): string
    {
        return hash('sha256', $this->cookieFactory->readFrom($request) ?? 'missing');
    }

    private function requireEnabled(): void
    {
        if (!$this->enabled) {
            throw new NotFoundHttpException();
        }
    }

    private function assertSameOrigin(Request $request): void
    {
        $submittedToken = $request->headers->get('X-Csrf-Token')
            ?? $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID)->getValue();

        if ($this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $submittedToken))) {
            return;
        }

        $this->securityEventLogger->csrfDenied($request);

        throw new AccessDeniedHttpException('The request failed CSRF validation.');
    }

    /** @param array<string, mixed> $data */
    private function json(array $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse($data, $status, [
            'Cache-Control' => 'no-store',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
