<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use App\Reporting\Application\RevokeReportAccess\RevokeReportAccess;
use App\Reporting\Application\RevokeReportAccess\RevokeReportAccessCommand;
use App\Shared\Infrastructure\Logging\SecurityEventLogger;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final readonly class RevokeReportAccessController
{
    private const CSRF_TOKEN_ID = 'report_access_grant_revocation';

    public function __construct(
        private RevokeReportAccess $revokeReportAccess,
        private ReportAccessCookieFactory $cookieFactory,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private SecurityEventLogger $securityEventLogger,
    ) {
    }

    #[Route(
        '/api/v1/reporter/access-grant',
        name: 'api_v1_reporter_revoke_access_grant',
        methods: ['DELETE'],
    )]
    #[OA\Delete(
        operationId: 'revokeReportAccessGrant',
        summary: 'Revoke the current report-access capability',
        description: 'Revokes the capability presented through the protected cookie, if any, and clears the cookie. Idempotent: it succeeds even when no active grant is presented.',
        security: [['reportAccessCookie' => []]],
        tags: ['Public reporting'],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'The capability was revoked and the cookie cleared.',
            ),
            new OA\Response(
                response: Response::HTTP_FORBIDDEN,
                description: 'The request failed CSRF validation.',
                content: new OA\MediaType(
                    mediaType: 'application/problem+json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/ProblemDetails',
                    ),
                ),
            ),
        ],
    )]
    public function __invoke(Request $request): Response
    {
        $submittedToken = $request->headers->get('X-Csrf-Token')
            ?? $this->csrfTokenManager
                ->getToken(self::CSRF_TOKEN_ID)
                ->getValue();

        if (
            !$this->csrfTokenManager->isTokenValid(
                new CsrfToken(self::CSRF_TOKEN_ID, $submittedToken),
            )
        ) {
            $this->securityEventLogger->csrfDenied($request);

            throw new AccessDeniedHttpException(
                'The request failed CSRF validation.',
            );
        }

        $capabilityHandle = $this->cookieFactory->readFrom($request);

        ($this->revokeReportAccess)(
            new RevokeReportAccessCommand($capabilityHandle ?? ''),
        );

        $response = new Response(
            null,
            Response::HTTP_NO_CONTENT,
            ['Cache-Control' => 'no-store'],
        );
        $response->headers->setCookie($this->cookieFactory->clear());

        return $response;
    }
}
