<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class ReportListCursor
{
    public function __construct(
        public DateTimeImmutable $createdAt,
        public Uuid $id,
    ) {
    }
}
