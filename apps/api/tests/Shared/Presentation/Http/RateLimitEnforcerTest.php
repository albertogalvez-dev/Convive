<?php

declare(strict_types=1);

namespace App\Tests\Shared\Presentation\Http;

use App\Shared\Infrastructure\Logging\SecurityEventLogger;
use App\Shared\Presentation\Http\RateLimitEnforcer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class RateLimitEnforcerTest extends TestCase
{
    public function testItAllowsRequestsWithinTheLimit(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $enforcer = new RateLimitEnforcer(new SecurityEventLogger($logger));

        $enforcer->enforce(
            $this->limiterFactory(limit: 2),
            'test_limiter',
            Request::create('/', server: ['REMOTE_ADDR' => '203.0.113.1']),
        );
    }

    public function testItThrowsAndLogsOnceTheLimitIsExceeded(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('warning')
            ->with(
                'rate_limit_exceeded',
                self::callback(
                    static fn (array $context): bool =>
                        $context['limiter'] === 'test_limiter',
                ),
            );

        $enforcer = new RateLimitEnforcer(new SecurityEventLogger($logger));
        $limiterFactory = $this->limiterFactory(limit: 1);
        $request = Request::create(
            '/',
            server: ['REMOTE_ADDR' => '203.0.113.2'],
        );

        $enforcer->enforce($limiterFactory, 'test_limiter', $request);

        $this->expectException(TooManyRequestsHttpException::class);

        $enforcer->enforce($limiterFactory, 'test_limiter', $request);
    }

    private function limiterFactory(int $limit): RateLimiterFactory
    {
        return new RateLimiterFactory(
            [
                'id' => 'test_limiter',
                'policy' => 'sliding_window',
                'limit' => $limit,
                'interval' => '1 minute',
            ],
            new InMemoryStorage(),
        );
    }
}
