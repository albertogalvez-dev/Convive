<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logging;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

/**
 * The single place allowed to write to the "security" log channel
 * (#41). Every method here takes a fixed, pre-selected set of fields —
 * never the request body, never a secret or capability handle. Adding
 * a new field to any of these methods is a deliberate decision, not
 * something a caller can opt into by passing more data through.
 */
final readonly class SecurityEventLogger
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.security')]
        private LoggerInterface $logger,
    ) {
    }

    public function rateLimitExceeded(string $limiterName, Request $request): void
    {
        $this->logger->warning('rate_limit_exceeded', $this->context(
            $request,
            [
                'path' => $this->safePath($request),
                'limiter' => $limiterName,
            ],
        ));
    }

    public function reportAccessDenied(Request $request): void
    {
        $this->logger->notice('report_access_denied', $this->context(
            $request,
            ['path' => $this->safePath($request)],
        ));
    }

    public function csrfDenied(Request $request): void
    {
        $this->logger->warning('csrf_denied', $this->context($request));
    }

    public function professionalAuthenticationFailed(Request $request): void
    {
        $this->logger->notice(
            'professional_authentication_failed',
            $this->context($request),
        );
    }

    public function professionalReportAccessDenied(Request $request): void
    {
        $this->logger->warning(
            'professional_report_access_denied',
            $this->context($request, [
                'path' => '/api/v1/professional/reports/{id}',
            ]),
        );
    }

    public function idempotentReplay(Request $request): void
    {
        $this->logger->info('idempotent_replay', $this->context($request));
    }

    public function attachmentScanUnavailable(): void
    {
        $this->logger->warning('attachment_scan_unavailable');
    }

    public function attachmentScanRejected(): void
    {
        $this->logger->warning('attachment_scan_rejected');
    }

    public function attachmentScanTimedOut(): void
    {
        $this->logger->warning('attachment_scan_timed_out');
    }

    public function attachmentDeletionFailed(): void
    {
        $this->logger->error('attachment_deletion_failed');
    }

    public function attachmentUploadRejected(Request $request): void
    {
        $this->logger->notice(
            'attachment_upload_rejected',
            $this->context($request, [
                'path' => '/api/v1/reporter/report/attachments',
            ]),
        );
    }

    public function reporterAttachmentDownloadDenied(Request $request): void
    {
        $this->logger->notice(
            'reporter_attachment_download_denied',
            $this->context($request, [
                'path' => '/api/v1/reporter/report/attachments/{id}/download',
            ]),
        );
    }

    public function reporterAttachmentDownloaded(Request $request): void
    {
        $this->logger->info(
            'reporter_attachment_downloaded',
            $this->context($request, [
                'path' => '/api/v1/reporter/report/attachments/{id}/download',
            ]),
        );
    }

    public function attachmentDownloadConcurrencyLimited(Request $request): void
    {
        $this->logger->warning(
            'attachment_download_concurrency_limited',
            $this->context($request, ['path' => $this->safePath($request)]),
        );
    }

    public function professionalAttachmentDownloadDenied(Request $request): void
    {
        $this->logger->warning(
            'professional_attachment_download_denied',
            $this->context($request, [
                'path' => '/api/v1/professional/reports/{reportId}/attachments/{attachmentId}/download',
            ]),
        );
    }

    public function professionalAttachmentDownloaded(Request $request): void
    {
        $this->logger->info(
            'professional_attachment_downloaded',
            $this->context($request, [
                'path' => '/api/v1/professional/reports/{reportId}/attachments/{attachmentId}/download',
            ]),
        );
    }

    /**
     * @param array<string, scalar> $extra
     *
     * @return array<string, scalar|null>
     */
    private function context(Request $request, array $extra = []): array
    {
        return [
            'path' => $request->getPathInfo(),
            'method' => $request->getMethod(),
            'client_ip' => $request->getClientIp(),
            ...$extra,
        ];
    }

    private function safePath(Request $request): string
    {
        return match ($request->attributes->getString('_route')) {
            'api_v1_reporter_upload_report_attachments',
            'api_v1_reporter_list_report_attachments' => '/api/v1/reporter/report/attachments',
            'api_v1_reporter_download_report_attachment' => '/api/v1/reporter/report/attachments/{id}/download',
            'api_v1_professional_list_report_attachments' => '/api/v1/professional/reports/{id}/attachments',
            'api_v1_professional_download_report_attachment' => '/api/v1/professional/reports/{reportId}/attachments/{attachmentId}/download',
            default => $request->getPathInfo(),
        };
    }
}
