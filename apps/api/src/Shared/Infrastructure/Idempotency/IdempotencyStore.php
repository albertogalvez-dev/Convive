<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Idempotency;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Detects a repeated request by a client-supplied idempotency key
 * (#41). Deliberately stores only a non-secret reference back to the
 * record a first request created — never the response body itself, so
 * a one-time secret (the report access secret) can never be replayed
 * a second time through this store. Protects the common case of a
 * sequential retry after a timeout or a double-click, not two
 * literally concurrent requests racing on the same key — see the
 * anti-abuse threat model for why that gap is accepted rather than
 * hidden.
 */
final readonly class IdempotencyStore
{
    private const TTL_SECONDS = 86_400; // 24 hours

    public function __construct(
        #[Autowire(service: 'app.idempotency')]
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function findRecordReference(string $scope, string $key): ?string
    {
        $item = $this->cache->getItem($this->cacheKey($scope, $key));

        if (!$item->isHit()) {
            return null;
        }

        $value = $item->get();

        return is_string($value) ? $value : null;
    }

    public function rememberRecordReference(
        string $scope,
        string $key,
        string $recordReference,
    ): void {
        $item = $this->cache->getItem($this->cacheKey($scope, $key));
        $item->set($recordReference);
        $item->expiresAfter(self::TTL_SECONDS);

        $this->cache->save($item);
    }

    private function cacheKey(string $scope, string $key): string
    {
        return 'idempotency.'.hash('sha256', $scope.'|'.$key);
    }
}
