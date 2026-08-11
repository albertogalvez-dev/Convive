<?php

declare(strict_types=1);

namespace App\Tests\Cases\Domain;

use App\Cases\Domain\CaseModality;
use App\Cases\Domain\CaseProtocolStage;
use App\Cases\Domain\CaseTask;
use App\Cases\Domain\CaseTaskKind;
use App\Cases\Domain\CaseTaskStatus;
use App\Cases\Domain\ManagedCase;
use App\Cases\Domain\WorkflowSourceAuthority;
use App\Cases\Domain\WorkflowSourceVersion;
use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CaseTaskTest extends TestCase
{
    public function testAnExternalCommunicationRemainsPendingUntilExplicitlyConfirmed(): void
    {
        $professional = $this->professional();
        $task = $this->task($professional, CaseTaskKind::ExternalCommunication);

        self::assertSame(CaseTaskStatus::Pending, $task->status());
        self::assertNull($task->resolvedAt());

        $completedAt = new DateTimeImmutable('2026-08-11T12:00:00+00:00');
        $task->complete($professional, $completedAt);

        self::assertSame(CaseTaskStatus::Completed, $task->status());
        self::assertSame($professional, $task->resolvedBy());
        self::assertSame($completedAt, $task->resolvedAt());
    }

    public function testOverdueIsDerivedAtTheStrictDueBoundaryAndOnlyWhilePending(): void
    {
        $professional = $this->professional();
        $task = $this->task($professional);

        self::assertFalse($task->isOverdue(new DateTimeImmutable('2026-08-11T11:00:00+00:00')));
        self::assertTrue($task->isOverdue(new DateTimeImmutable('2026-08-11T11:00:01+00:00')));

        $task->complete($professional, new DateTimeImmutable('2026-08-11T12:00:00+00:00'));
        self::assertFalse($task->isOverdue(new DateTimeImmutable('2026-08-12T12:00:00+00:00')));
    }

    public function testNotApplicableRequiresAReasonAndIsTerminal(): void
    {
        $professional = $this->professional();
        $task = $this->task($professional);
        $task->markNotApplicable(
            $professional,
            new DateTimeImmutable('2026-08-11T10:30:00+00:00'),
            'No external recipient applies to this fictional case.',
        );

        self::assertSame(CaseTaskStatus::NotApplicable, $task->status());
        self::assertSame('No external recipient applies to this fictional case.', $task->notApplicableReason());

        $this->expectException(LogicException::class);
        $task->complete($professional, new DateTimeImmutable('2026-08-11T10:31:00+00:00'));
    }

    public function testSourceAuthorityIsExplicitAndVersioned(): void
    {
        $source = $this->source(WorkflowSourceAuthority::Recommended);

        self::assertSame('PRESENTED-2026-04-15', $source->version());
        self::assertSame(WorkflowSourceAuthority::Recommended, $source->authority());
        self::assertSame('ES', $source->territory());
    }

    private function task(
        Professional $professional,
        CaseTaskKind $kind = CaseTaskKind::InternalAction,
    ): CaseTask {
        $organisation = new Organisation(Uuid::v7(), 'Fictional School', PublicReportingIdentifier::generate());
        $managedCase = new ManagedCase(
            Uuid::v7(),
            $organisation,
            $professional,
            new DateTimeImmutable('2026-08-11T09:00:00+00:00'),
            CaseModality::Digital,
        );

        return new CaseTask(
            Uuid::v7(),
            $managedCase,
            $professional,
            $this->source(WorkflowSourceAuthority::Binding),
            CaseProtocolStage::InspectionCommunication,
            $kind,
            'Confirm communication with the Education Inspectorate',
            new DateTimeImmutable('2026-08-11T11:00:00+00:00'),
            $professional,
            new DateTimeImmutable('2026-08-11T10:00:00+00:00'),
        );
    }

    private function source(WorkflowSourceAuthority $authority): WorkflowSourceVersion
    {
        return new WorkflowSourceVersion(
            Uuid::v7(),
            'ES-MEFPD-FRAMEWORK-2026-04-15',
            'PRESENTED-2026-04-15',
            'National reference framework',
            'https://www.educacionfpydeportes.gob.es/',
            'ES',
            $authority,
            new DateTimeImmutable('2026-04-15'),
            new DateTimeImmutable('2026-08-11'),
        );
    }

    private function professional(): Professional
    {
        return new Professional(
            Uuid::v7(),
            'Fictional Professional',
            ProfessionalEmail::fromString(Uuid::v7()->toRfc4122().'@example.invalid'),
            new DateTimeImmutable('2026-08-11T08:00:00+00:00'),
        );
    }
}
