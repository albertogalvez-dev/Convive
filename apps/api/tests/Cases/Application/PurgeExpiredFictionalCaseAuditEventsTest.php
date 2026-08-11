<?php

declare(strict_types=1);

namespace App\Tests\Cases\Application;

use App\Cases\Application\PurgeExpiredFictionalCaseAuditEvents;
use App\Cases\Domain\CaseAuditEventRepository;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;

final class PurgeExpiredFictionalCaseAuditEventsTest extends TestCase
{
    public function testCleanupRefusesToRunOutsideExplicitFictionalDemoMode(): void
    {
        $events = $this->createMock(CaseAuditEventRepository::class);
        $events->expects(self::never())->method('purgeBefore');

        $this->expectException(LogicException::class);
        (new PurgeExpiredFictionalCaseAuditEvents($events, false))(
            1,
            new DateTimeImmutable('2026-08-11T12:00:00+00:00'),
        );
    }
}
