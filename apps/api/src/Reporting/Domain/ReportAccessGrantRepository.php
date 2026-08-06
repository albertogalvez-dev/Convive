<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

interface ReportAccessGrantRepository
{
    public function save(ReportAccessGrant $grant): void;

    public function findByCapability(ReportAccessCapability $capability): ?ReportAccessGrant;
}
