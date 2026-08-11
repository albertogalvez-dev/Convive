<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use DateTimeImmutable;

interface CaseAuditEventRepository
{
    public function append(CaseAuditEvent $event): void;

    public function flush(): void;

    /** @return list<CaseAuditEvent> */
    public function findByCase(ManagedCase $managedCase): array;

    /**
     * Removes only expired fictional audit events through the controlled
     * database retention boundary.
     */
    public function purgeBefore(DateTimeImmutable $cutoff, int $limit): int;
}
