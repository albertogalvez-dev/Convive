<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use DateTimeImmutable;

interface ProfessionalExportEventRepository
{
    public function append(ProfessionalExportEvent $event): void;

    public function flush(): void;

    /** Removes only expired fictional export events through the retention boundary. */
    public function purgeBefore(DateTimeImmutable $cutoff, int $limit): int;
}
