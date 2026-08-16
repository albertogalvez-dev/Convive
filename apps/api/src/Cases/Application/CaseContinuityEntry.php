<?php

declare(strict_types=1);

namespace App\Cases\Application;

use App\Cases\Domain\CaseContinuityReason;
use App\Cases\Domain\ManagedCase;
use App\Professionals\Domain\Professional;
use DateTimeImmutable;

/**
 * One line of the operational continuity list. It deliberately carries no case
 * content: an administrator reads this to decide whether a reassignment is
 * needed, which is an organisational act, not a reason to see the case.
 */
final readonly class CaseContinuityEntry
{
    public function __construct(
        public ManagedCase $managedCase,
        public Professional $responsible,
        public CaseContinuityReason $reason,
        public ?DateTimeImmutable $earliestOverdueAt,
    ) {
    }
}
