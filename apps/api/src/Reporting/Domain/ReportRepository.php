<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use App\Organisations\Domain\Organisation;
use Symfony\Component\Uid\Uuid;

interface ReportRepository
{
    public function save(Report $report): void;

    public function findByPublicReference(string $publicReference): ?Report;

    public function findByAccessSecret(ReportAccessSecret $accessSecret): ?Report;

    /**
     * @param non-empty-list<Organisation> $organisations
     */
    public function findPageForOrganisations(
        array $organisations,
        ?ReportStatus $status,
        ?ReportListCursor $cursor,
        int $limit,
    ): ReportPage;

    /**
     * @param non-empty-list<Organisation> $organisations
     */
    public function findByIdForOrganisations(Uuid $id, array $organisations): ?Report;
}
