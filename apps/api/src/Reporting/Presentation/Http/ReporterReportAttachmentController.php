<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use App\Reporting\Application\QuarantineReportAttachments\QuarantineReportAttachments;
use App\Reporting\Domain\AttachmentDescription;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportAttachmentRepository;
use App\Shared\Infrastructure\Logging\SecurityEventLogger;
use App\Shared\Presentation\Http\RateLimitEnforcer;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class ReporterReportAttachmentController
{
    private const CSRF_TOKEN_ID = 'report_attachment_upload';

    public function __construct(
        private ReportAccessGuard $guard,
        private ReportAccessCookieFactory $cookieFactory,
        private ReportAttachmentUploadInspector $uploadInspector,
        private QuarantineReportAttachments $quarantineAttachments,
        private ReportAttachmentRepository $attachments,
        private ReportAttachmentPresenter $presenter,
        private PrivateReportAttachmentDownloadResponder $downloadResponder,
        private RateLimitEnforcer $rateLimitEnforcer,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private SecurityEventLogger $securityEventLogger,
        #[Autowire(service: 'limiter.report_attachment_upload_ip')]
        private RateLimiterFactory $uploadIpRateLimiter,
        #[Autowire(service: 'limiter.report_attachment_upload_capability')]
        private RateLimiterFactory $uploadCapabilityRateLimiter,
        #[Autowire(service: 'limiter.report_attachment_read_ip')]
        private RateLimiterFactory $readIpRateLimiter,
        #[Autowire(service: 'limiter.report_attachment_read_capability')]
        private RateLimiterFactory $readCapabilityRateLimiter,
        #[Autowire(service: 'limiter.report_attachment_download_ip')]
        private RateLimiterFactory $downloadIpRateLimiter,
        #[Autowire(service: 'limiter.report_attachment_download_capability')]
        private RateLimiterFactory $downloadCapabilityRateLimiter,
    ) {
    }

    #[Route(
        '/api/v1/reporter/report/attachments',
        name: 'api_v1_reporter_upload_report_attachments',
        methods: ['POST'],
    )]
    #[OA\Post(
        operationId: 'uploadReporterReportAttachments',
        summary: "Upload evidence for the reporter's own report",
        description: 'Accepts up to three PDF, JPEG or PNG multipart files only after capability and same-origin checks. Files remain unavailable until private scanning completes.',
        security: [['reportAccessCookie' => []]],
        tags: ['Public reporting'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    additionalProperties: false,
                    required: ['attachments'],
                    properties: [
                        new OA\Property(
                            property: 'attachments',
                            type: 'array',
                            maxItems: 3,
                            items: new OA\Items(type: 'string', format: 'binary'),
                        ),
                        new OA\Property(
                            property: 'descriptions',
                            type: 'array',
                            maxItems: 3,
                            items: new OA\Items(type: 'string', maxLength: AttachmentDescription::MAX_LENGTH),
                        ),
                    ],
                ),
            ),
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Private attachment records were quarantined for scanning.',
                content: new OA\JsonContent(
                    type: 'object',
                    additionalProperties: false,
                    required: ['items'],
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ReporterAttachment'),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'The report access capability was not accepted.'),
            new OA\Response(response: 403, description: 'The upload failed CSRF validation.'),
            new OA\Response(response: 409, description: 'The report attachment capacity was reached.'),
            new OA\Response(response: 413, description: 'An attachment exceeds the accepted size.'),
            new OA\Response(response: 415, description: 'A submitted attachment type is not accepted.'),
            new OA\Response(response: 422, description: 'The multipart upload is invalid.'),
            new OA\Response(response: 429, description: 'The upload rate limit was exceeded.'),
        ],
    )]
    public function upload(Request $request): JsonResponse
    {
        $this->assertSameOrigin($request);
        $this->rateLimitEnforcer->enforce(
            $this->uploadIpRateLimiter,
            'report_attachment_upload_ip',
            $request,
        );
        $this->rateLimitEnforcer->enforce(
            $this->uploadCapabilityRateLimiter,
            'report_attachment_upload_capability',
            $request,
            $this->cookieFactory->readFrom($request),
        );
        $grant = $this->resolveGrant($request);
        $attachments = ($this->quarantineAttachments)(
            $grant->report(),
            $this->uploadInspector->inspect($request),
        );

        return $this->json(
            ['items' => array_map($this->presenter->reporter(...), $attachments)],
            Response::HTTP_CREATED,
        );
    }

    #[Route(
        '/api/v1/reporter/report/attachments',
        name: 'api_v1_reporter_list_report_attachments',
        methods: ['GET'],
    )]
    #[OA\Get(
        operationId: 'listReporterReportAttachments',
        summary: "List the reporter's attachment states",
        description: 'Returns only a minimal processing or unavailable state until an attachment is scanned and available. It never exposes a scanner verdict or storage metadata.',
        security: [['reportAccessCookie' => []]],
        tags: ['Public reporting'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'The reporter attachment states.',
                content: new OA\JsonContent(
                    type: 'object',
                    additionalProperties: false,
                    required: ['items'],
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ReporterAttachment'),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'The report access capability was not accepted.'),
            new OA\Response(response: 429, description: 'The read rate limit was exceeded.'),
        ],
    )]
    public function list(Request $request): JsonResponse
    {
        $this->enforceReadRateLimit($request);
        $grant = $this->resolveGrant($request);

        return $this->json([
            'items' => array_map(
                $this->presenter->reporter(...),
                $this->attachments->findByReport($grant->report()),
            ),
        ]);
    }

    #[Route(
        '/api/v1/reporter/report/attachments/{id}/download',
        name: 'api_v1_reporter_download_report_attachment',
        methods: ['GET'],
    )]
    #[OA\Get(
        operationId: 'downloadReporterReportAttachment',
        summary: "Download an available attachment from the reporter's own report",
        description: 'Streams an available private object with a generated filename. Files that are processing, rejected or deleted are indistinguishable from unknown attachments.',
        security: [['reportAccessCookie' => []]],
        tags: ['Public reporting'],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'The authorised attachment stream.'),
            new OA\Response(response: 401, description: 'The report access capability was not accepted.'),
            new OA\Response(response: 404, description: 'The attachment is unavailable.'),
            new OA\Response(response: 429, description: 'The download rate or concurrent stream limit was exceeded.'),
        ],
    )]
    public function download(string $id, Request $request): StreamedResponse
    {
        $this->rateLimitEnforcer->enforce(
            $this->downloadIpRateLimiter,
            'report_attachment_download_ip',
            $request,
        );
        $this->rateLimitEnforcer->enforce(
            $this->downloadCapabilityRateLimiter,
            'report_attachment_download_capability',
            $request,
            $this->cookieFactory->readFrom($request),
        );
        $grant = $this->resolveGrant($request);

        if (!Uuid::isValid($id)) {
            $this->securityEventLogger->reporterAttachmentDownloadDenied($request);

            throw new ReportAttachmentUnavailableHttpException();
        }

        $attachment = $this->attachments->findByIdForReport(
            Uuid::fromString($id),
            $grant->report(),
        );

        if ($attachment === null || !$attachment->isAvailable()) {
            $this->securityEventLogger->reporterAttachmentDownloadDenied($request);

            throw new ReportAttachmentUnavailableHttpException();
        }

        try {
            $response = $this->downloadResponder->respond($attachment);
        } catch (ReportAttachmentUnavailableHttpException $exception) {
            $this->securityEventLogger->reporterAttachmentDownloadDenied($request);

            throw $exception;
        }

        $this->securityEventLogger->reporterAttachmentDownloaded($request);

        return $response;
    }

    private function enforceReadRateLimit(Request $request): void
    {
        $this->rateLimitEnforcer->enforce(
            $this->readIpRateLimiter,
            'report_attachment_read_ip',
            $request,
        );
        $this->rateLimitEnforcer->enforce(
            $this->readCapabilityRateLimiter,
            'report_attachment_read_capability',
            $request,
            $this->cookieFactory->readFrom($request),
        );
    }

    private function resolveGrant(Request $request): \App\Reporting\Domain\ReportAccessGrant
    {
        return $this->guard->resolve($request)
            ?? throw new ReportAccessCapabilityRejectedHttpException();
    }

    private function assertSameOrigin(Request $request): void
    {
        $submittedToken = $request->headers->get('X-Csrf-Token')
            ?? $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID)->getValue();

        if ($this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $submittedToken))) {
            return;
        }

        $this->securityEventLogger->csrfDenied($request);

        throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException(
            'The request failed CSRF validation.',
        );
    }

    /** @param array<string, mixed> $data */
    private function json(array $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse($data, $status, ['Cache-Control' => 'no-store']);
    }
}
