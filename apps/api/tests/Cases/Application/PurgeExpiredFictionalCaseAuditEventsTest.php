<?php

declare(strict_types=1);

namespace App\Tests\Cases\Application;

use App\Cases\Application\PurgeExpiredFictionalCaseAuditEvents;
use App\Cases\Domain\CaseAuditEventRepository;
use App\Cases\Domain\ProfessionalExportEventRepository;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;

final class PurgeExpiredFictionalCaseAuditEventsTest extends TestCase
{
    public function testCleanupRespectsOneSharedBoundedBatchAcrossAuditRecords(): void
    {
        $now = new DateTimeImmutable('2026-08-11T12:00:00+00:00');
        $events = $this->createMock(CaseAuditEventRepository::class);
        $professionalExportEvents = $this->createMock(ProfessionalExportEventRepository::class);
        $events->expects(self::once())
            ->method('purgeBefore')
            ->with(new DateTimeImmutable('2026-07-12T12:00:00+00:00'), 1)
            ->willReturn(1);
        $professionalExportEvents->expects(self::never())->method('purgeBefore');

        self::assertSame(1, (new PurgeExpiredFictionalCaseAuditEvents(
            $events,
            $professionalExportEvents,
            true,
        ))(1, $now));
    }

    public function testCleanupRefusesToRunOutsideExplicitFictionalDemoMode(): void
    {
        $events = $this->createMock(CaseAuditEventRepository::class);
        $professionalExportEvents = $this->createMock(ProfessionalExportEventRepository::class);
        $events->expects(self::never())->method('purgeBefore');
        $professionalExportEvents->expects(self::never())->method('purgeBefore');

        $this->expectException(LogicException::class);
        (new PurgeExpiredFictionalCaseAuditEvents($events, $professionalExportEvents, false))(
            1,
            new DateTimeImmutable('2026-08-11T12:00:00+00:00'),
        );
    }
}
