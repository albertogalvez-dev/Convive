<?php

declare(strict_types=1);

namespace App\Cases\Application;

use App\Cases\Domain\CaseAssignment;
use App\Cases\Domain\CaseCommunication;
use App\Cases\Domain\CaseInvolvedPerson;
use App\Cases\Domain\CaseTask;
use App\Cases\Domain\ManagedCase;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportTriageDecision;

final readonly class CaseWorkspaceDetail
{
    /**
     * @param list<CaseInvolvedPerson> $people
     * @param list<CaseAssignment> $assignments
     * @param list<CaseTask> $tasks
     * @param list<CaseCommunication> $communications
     * @param list<ReportAttachment> $evidence
     */
    public function __construct(
        public ManagedCase $managedCase,
        public CaseAssignment $currentAssignment,
        public array $people,
        public array $assignments,
        public array $tasks,
        public array $communications,
        public ?Report $sourceReport,
        public ?ReportTriageDecision $sourceDecision,
        public array $evidence,
    ) {
    }
}
