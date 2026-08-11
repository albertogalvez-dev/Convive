<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Domain;

use App\Cases\Domain\ManagedCase;
use App\Cases\Domain\CaseModality;
use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportTriageDecision;
use App\Reporting\Domain\ReportTriageOutcome;
use App\Reporting\Domain\ReportTriageReason;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ReportTriageDecisionTest extends TestCase
{
    #[DataProvider('validOutcomes')]
    public function testItRecordsAnAttributedDecision(ReportTriageOutcome $outcome): void
    {
        $organisation = $this->organisation('43A');
        $professional = $this->professional($organisation);
        $report = $this->report($organisation);
        $decidedAt = new DateTimeImmutable('2026-08-11T06:00:00+00:00');
        $case = $outcome === ReportTriageOutcome::LinkToCase
            ? new ManagedCase(Uuid::v7(), $organisation, $professional, $decidedAt, CaseModality::Unknown)
            : null;

        $decision = new ReportTriageDecision(
            Uuid::v7(),
            $report,
            $professional,
            $outcome,
            ReportTriageReason::fromString('Documented fictional triage rationale.'),
            $decidedAt,
            $case,
        );

        self::assertSame($report, $decision->report());
        self::assertSame($organisation, $decision->organisation());
        self::assertSame($professional, $decision->decidedBy());
        self::assertSame($outcome, $decision->outcome());
        self::assertSame('Documented fictional triage rationale.', $decision->reason()->toString());
        self::assertSame($decidedAt, $decision->decidedAt());
        self::assertSame($case, $decision->managedCase());
    }

    /** @return iterable<string, array{ReportTriageOutcome}> */
    public static function validOutcomes(): iterable
    {
        foreach (ReportTriageOutcome::cases() as $outcome) {
            yield $outcome->value => [$outcome];
        }
    }

    public function testOnlyTheLinkOutcomeAcceptsACase(): void
    {
        $organisation = $this->organisation('43B');
        $professional = $this->professional($organisation);
        $case = new ManagedCase(Uuid::v7(), $organisation, $professional, new DateTimeImmutable(), CaseModality::Unknown);

        $this->expectException(\LogicException::class);

        new ReportTriageDecision(
            Uuid::v7(),
            $this->report($organisation),
            $professional,
            ReportTriageOutcome::Dismiss,
            ReportTriageReason::fromString('A valid but non-linking fictional decision.'),
            new DateTimeImmutable(),
            $case,
        );
    }

    public function testALinkCannotCrossTheOrganisationBoundary(): void
    {
        $reportOrganisation = $this->organisation('43C');
        $caseOrganisation = $this->organisation('43D');
        $professional = $this->professional($reportOrganisation);
        $case = new ManagedCase(Uuid::v7(), $caseOrganisation, $professional, new DateTimeImmutable(), CaseModality::Unknown);

        $this->expectException(\LogicException::class);

        new ReportTriageDecision(
            Uuid::v7(),
            $this->report($reportOrganisation),
            $professional,
            ReportTriageOutcome::LinkToCase,
            ReportTriageReason::fromString('Cross-organisation linking must be rejected.'),
            new DateTimeImmutable(),
            $case,
        );
    }

    #[DataProvider('invalidReasons')]
    public function testItRejectsAnUnusableReason(string $reason): void
    {
        $this->expectException(InvalidArgumentException::class);

        ReportTriageReason::fromString($reason);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidReasons(): iterable
    {
        yield 'blank' => ['   '];
        yield 'short' => ['Too short'];
        yield 'long' => [str_repeat('a', ReportTriageReason::MAX_LENGTH + 1)];
    }

    private function organisation(string $suffix): Organisation
    {
        return new Organisation(
            Uuid::v7(),
            'Fictional Triage School '.$suffix,
            PublicReportingIdentifier::fromString('ORG_43'.$suffix.'00000000000'),
        );
    }

    private function professional(Organisation $organisation): Professional
    {
        return new Professional(
            Uuid::v7(),
            'Fictional Professional',
            ProfessionalEmail::fromString(
                'triage-'.$organisation->id()->toRfc4122().'@example.invalid',
            ),
            new DateTimeImmutable('2026-08-11T05:00:00+00:00'),
        );
    }

    private function report(Organisation $organisation): Report
    {
        return Report::create(
            $organisation,
            SituationDescription::fromString('A fictional situation requires careful assessment.'),
            SituationContext::Unknown,
        )->report;
    }
}
