<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Idempotency;

use App\Shared\Infrastructure\Idempotency\IdempotencyStore;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class IdempotencyStoreTest extends TestCase
{
    public function testItReturnsNullWhenNoRecordWasRemembered(): void
    {
        $store = new IdempotencyStore(new ArrayAdapter());

        self::assertNull($store->findRecordReference('org-a', 'key-1'));
    }

    public function testItReturnsTheRememberedReferenceForTheSameScopeAndKey(): void
    {
        $store = new IdempotencyStore(new ArrayAdapter());

        $store->rememberRecordReference('org-a', 'key-1', 'REPORT-REFERENCE-1');

        self::assertSame(
            'REPORT-REFERENCE-1',
            $store->findRecordReference('org-a', 'key-1'),
        );
    }

    public function testTheSameKeyUnderADifferentScopeIsIndependent(): void
    {
        $store = new IdempotencyStore(new ArrayAdapter());

        $store->rememberRecordReference('org-a', 'key-1', 'REPORT-REFERENCE-1');

        self::assertNull($store->findRecordReference('org-b', 'key-1'));
    }

    public function testADifferentKeyUnderTheSameScopeIsIndependent(): void
    {
        $store = new IdempotencyStore(new ArrayAdapter());

        $store->rememberRecordReference('org-a', 'key-1', 'REPORT-REFERENCE-1');

        self::assertNull($store->findRecordReference('org-a', 'key-2'));
    }
}
