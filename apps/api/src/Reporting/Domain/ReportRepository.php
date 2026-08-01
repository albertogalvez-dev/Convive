<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

interface ReportRepository
{
    public function save(Report $report): void;

    public function findByPublicReference(string $publicReference): ?Report;
}
