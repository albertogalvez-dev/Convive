<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use App\Cases\Domain\ManagedCase;
use App\Organisations\Domain\Organisation;
use App\Professionals\Domain\Professional;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'report_triage_decisions')]
#[ORM\Index(name: 'idx_report_triage_history', columns: ['report_id', 'decided_at', 'id'])]
class ReportTriageDecision
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Report::class)]
    #[ORM\JoinColumn(name: 'report_id', referencedColumnName: 'id', nullable: false)]
    private Report $report;

    #[ORM\ManyToOne(targetEntity: Organisation::class)]
    #[ORM\JoinColumn(name: 'organisation_id', referencedColumnName: 'id', nullable: false)]
    private Organisation $organisation;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'decided_by_professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $decidedBy;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: ReportTriageOutcome::class)]
    private ReportTriageOutcome $outcome;

    #[ORM\Column(type: Types::TEXT)]
    private string $reason;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $decidedAt;

    #[ORM\OneToOne(targetEntity: Report::class)]
    #[ORM\JoinColumn(name: 'terminal_report_id', referencedColumnName: 'id', nullable: true, unique: true)]
    private ?Report $terminalReport;

    #[ORM\OneToOne(targetEntity: ManagedCase::class)]
    #[ORM\JoinColumn(name: 'case_id', referencedColumnName: 'id', nullable: true, unique: true)]
    private ?ManagedCase $managedCase;

    public function __construct(
        Uuid $id,
        Report $report,
        Professional $decidedBy,
        ReportTriageOutcome $outcome,
        ReportTriageReason $reason,
        DateTimeImmutable $decidedAt,
        ?ManagedCase $managedCase = null,
    ) {
        if (($outcome === ReportTriageOutcome::LinkToCase) !== ($managedCase instanceof ManagedCase)) {
            throw new \LogicException('Only a link-to-case decision must contain a managed case.');
        }

        if ($managedCase !== null && !$managedCase->organisation()->id()->equals($report->organisation()->id())) {
            throw new \LogicException('A report can only link to a case in the same organisation.');
        }

        $this->id = $id;
        $this->report = $report;
        $this->organisation = $report->organisation();
        $this->decidedBy = $decidedBy;
        $this->outcome = $outcome;
        $this->reason = $reason->toString();
        $this->decidedAt = $decidedAt;
        $this->terminalReport = $outcome->isTerminal() ? $report : null;
        $this->managedCase = $managedCase;
    }

    public function id(): Uuid { return $this->id; }
    public function report(): Report { return $this->report; }
    public function organisation(): Organisation { return $this->organisation; }
    public function decidedBy(): Professional { return $this->decidedBy; }
    public function outcome(): ReportTriageOutcome { return $this->outcome; }
    public function reason(): ReportTriageReason { return ReportTriageReason::fromString($this->reason); }
    public function decidedAt(): DateTimeImmutable { return $this->decidedAt; }
    public function managedCase(): ?ManagedCase { return $this->managedCase; }
}
