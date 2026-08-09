<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use App\Shared\Infrastructure\Logging\SecurityEventLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class RateLimitEnforcer
{
    public function __construct(
        private SecurityEventLogger $securityEventLogger,
    ) {
    }

    /**
     * @throws TooManyRequestsHttpException when the limit is exceeded
     */
    public function enforce(
        RateLimiterFactory $limiter,
        string $limiterName,
        Request $request,
        ?string $credentialScope = null,
    ): void {
        $key = hash(
            'sha256',
            (string) $request->getClientIp()."\0".($credentialScope ?? ''),
        );

        $limit = $limiter
            ->create($key)
            ->consume();

        if (!$limit->isAccepted()) {
            $this->securityEventLogger->rateLimitExceeded(
                $limiterName,
                $request,
            );

            throw new TooManyRequestsHttpException(
                $limit->getRetryAfter()->getTimestamp() - time(),
            );
        }
    }
}
