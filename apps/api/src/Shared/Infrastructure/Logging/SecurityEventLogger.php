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
            ['limiter' => $limiterName],
        ));
    }

    public function reportAccessDenied(Request $request): void
    {
        $this->logger->notice('report_access_denied', $this->context($request));
    }

    public function csrfDenied(Request $request): void
    {
        $this->logger->warning('csrf_denied', $this->context($request));
    }

    public function idempotentReplay(Request $request): void
    {
        $this->logger->info('idempotent_replay', $this->context($request));
    }

    /**
     * @param array<string, scalar> $extra
     *
     * @return array<string, scalar|null>
     */
    private function context(Request $request, array $extra = []): array
    {
        return [
            ...$extra,
            'path' => $request->getPathInfo(),
            'method' => $request->getMethod(),
            'client_ip' => $request->getClientIp(),
        ];
    }
}
