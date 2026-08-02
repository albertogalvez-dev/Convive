<?php

declare(strict_types=1);

namespace App\Reporting\Application\SubmitAnonymousReport;

use App\Organisations\Domain\OrganisationRepository;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportRepository;

final readonly class SubmitAnonymousReport
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private ReportRepository $reportRepository,
    ) {
    }

    public function __invoke(
        SubmitAnonymousReportCommand $command,
    ): SubmitAnonymousReportResult {
        $organisation = $this->organisationRepository
            ->findByPublicReportingIdentifier(
                $command->organisationIdentifier,
            );

        if ($organisation === null) {
            throw ReportingOrganisationNotFound::withIdentifier(
                $command->organisationIdentifier,
            );
        }

        $creationResult = Report::create(
            $organisation,
            $command->situationDescription,
            $command->situationContext,
        );

        $report = $creationResult->report;

        $this->reportRepository->save($report);

        return new SubmitAnonymousReportResult(
            $report->publicReference(),
            $creationResult->plainAccessSecret,
            $report->status(),
            $report->createdAt(),
        );
    }
}
