<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

final readonly class ReportPage
{
    /**
     * @param list<Report> $items
     */
    public function __construct(
        public array $items,
        public ?ReportListCursor $nextCursor,
    ) {
    }
}
