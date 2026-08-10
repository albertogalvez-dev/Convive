<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Logging;

use App\Shared\Infrastructure\Logging\SecurityEventLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class SecurityEventLoggerTest extends TestCase
{
    public function testRateLimitExceededLogsTheLimiterNameAndRequestContext(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('warning')
            ->with(
                'rate_limit_exceeded',
                self::callback(
                    static fn (array $context): bool =>
                        $context['limiter'] === 'report_submission'
                        && $context['path'] === '/api/v1/public/organisations/ORG_TEST/reports'
                        && $context['method'] === 'POST',
                ),
            );

        (new SecurityEventLogger($logger))->rateLimitExceeded(
            'report_submission',
            Request::create(
                '/api/v1/public/organisations/ORG_TEST/reports',
                'POST',
            ),
        );
    }

    public function testReportAccessDeniedLogsOnlyRequestContext(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('notice')
            ->with(
                'report_access_denied',
                self::callback(
                    static fn (array $context): bool =>
                        array_keys($context) === ['path', 'method', 'client_ip'],
                ),
            );

        (new SecurityEventLogger($logger))->reportAccessDenied(
            Request::create('/api/v1/public/report-access-grants', 'POST'),
        );
    }

    public function testCsrfDeniedLogsAWarning(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning')->with('csrf_denied');

        (new SecurityEventLogger($logger))->csrfDenied(
            Request::create('/api/v1/reporter/access-grant', 'DELETE'),
        );
    }

    public function testProfessionalReportDenialDoesNotLogTheObjectIdentifier(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('warning')
            ->with(
                'professional_report_access_denied',
                self::callback(
                    static fn (array $context): bool =>
                        $context['path'] === '/api/v1/professional/reports/{id}'
                        && !str_contains(
                            (string) json_encode($context),
                            '0192a5c0-9999-7000-8000-000000000031',
                        ),
                ),
            );

        (new SecurityEventLogger($logger))->professionalReportAccessDenied(
            Request::create(
                '/api/v1/professional/reports/0192a5c0-9999-7000-8000-000000000031',
                'GET',
            ),
        );
    }

    public function testIdempotentReplayLogsAnInfoLevelEvent(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('info')->with('idempotent_replay');

        (new SecurityEventLogger($logger))->idempotentReplay(
            Request::create(
                '/api/v1/public/organisations/ORG_TEST/reports',
                'POST',
            ),
        );
    }

    public function testReporterAttachmentDownloadAuditNeverLogsTheAttachmentIdentifier(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('info')
            ->with(
                'reporter_attachment_downloaded',
                self::callback(
                    static fn (array $context): bool =>
                        $context['path'] === '/api/v1/reporter/report/attachments/{id}/download'
                        && !str_contains(
                            (string) json_encode($context),
                            '0192a5c0-9999-7000-8000-000000000037',
                        ),
                ),
            );
        $request = Request::create(
            '/api/v1/reporter/report/attachments/0192a5c0-9999-7000-8000-000000000037/download',
            'GET',
        );

        (new SecurityEventLogger($logger))->reporterAttachmentDownloaded($request);
    }

    public function testAttachmentRateLimitAuditUsesTheRouteTemplate(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('warning')
            ->with(
                'rate_limit_exceeded',
                self::callback(
                    static fn (array $context): bool =>
                        $context['path'] === '/api/v1/reporter/report/attachments/{id}/download'
                        && !str_contains(
                            (string) json_encode($context),
                            '0192a5c0-9999-7000-8000-000000000037',
                        ),
                ),
            );
        $request = Request::create(
            '/api/v1/reporter/report/attachments/0192a5c0-9999-7000-8000-000000000037/download',
            'GET',
        );
        $request->attributes->set('_route', 'api_v1_reporter_download_report_attachment');

        (new SecurityEventLogger($logger))->rateLimitExceeded(
            'report_attachment_download_capability',
            $request,
        );
    }

    public function testAttachmentDownloadConcurrencyAuditUsesTheRouteTemplate(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('warning')
            ->with(
                'attachment_download_concurrency_limited',
                self::callback(
                    static fn (array $context): bool =>
                        $context['path'] === '/api/v1/reporter/report/attachments/{id}/download'
                        && !str_contains(
                            (string) json_encode($context),
                            '0192a5c0-9999-7000-8000-000000000037',
                        ),
                ),
            );
        $request = Request::create(
            '/api/v1/reporter/report/attachments/0192a5c0-9999-7000-8000-000000000037/download',
            'GET',
        );
        $request->attributes->set('_route', 'api_v1_reporter_download_report_attachment');

        (new SecurityEventLogger($logger))->attachmentDownloadConcurrencyLimited($request);
    }
}
