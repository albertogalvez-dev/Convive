<?php

declare(strict_types=1);

namespace App\Reporting\Application\ProfessionalInbox;

use App\Organisations\Domain\Organisation;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportFollowUpEntryRepository;
use App\Reporting\Domain\ReportListCursor;
use App\Reporting\Domain\ReportPage;
use App\Reporting\Domain\ReportRepository;
use App\Reporting\Domain\ReportReviewReason;
use App\Reporting\Domain\ReportStatus;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class ProfessionalReportInbox
{
    private const MAXIMUM_FOLLOW_UP_ENTRIES = 100;

    public function __construct(
        private ReportRepository $reports,
        private ReportFollowUpEntryRepository $followUpEntries,
    ) {
    }

    /** @param non-empty-list<Organisation> $organisations */
    public function list(
        array $organisations,
        ?ReportStatus $status,
        ?ReportListCursor $cursor,
        int $limit,
    ): ReportPage {
        return $this->reports->findPageForOrganisations(
            $organisations,
            $status,
            $cursor,
            $limit,
        );
    }

    /** @param non-empty-list<Organisation> $organisations */
    public function detail(Uuid $id, array $organisations): ?ProfessionalReportDetail
    {
        $report = $this->reports->findByIdForOrganisations($id, $organisations);

        if ($report === null) {
            return null;
        }

        return new ProfessionalReportDetail(
            $report,
            $this->followUpEntries->findByReportOrderedByCreatedAt(
                $report,
                self::MAXIMUM_FOLLOW_UP_ENTRIES,
            ),
        );
    }

    /** @param non-empty-list<Organisation> $organisations */
    public function review(
        Uuid $id,
        array $organisations,
        ReportReviewReason $reason,
        Uuid $professionalId,
    ): ?Report {
        $report = $this->reports->findByIdForOrganisations($id, $organisations);

        if ($report === null) {
            return null;
        }

        $report->review(
            $reason,
            $professionalId,
            DateTimeImmutable::createFromTimestamp(microtime(true)),
        );
        $this->reports->save($report);

        return $report;
    }
}
