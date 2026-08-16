<?php

declare(strict_types=1);

namespace App\Cases\Domain;

/**
 * Why a case appears on the operational continuity list. These are operational
 * signals about the work, never a safeguarding judgement about the case.
 */
enum CaseContinuityReason: string
{
    /** The responsible professional recorded an absence covering today and nobody was reassigned. */
    case ResponsibleAbsent = 'responsible_absent';

    /** The case holds at least one pending task past its due date. */
    case OverdueTask = 'overdue_task';

    /** Both signals apply at once. */
    case AbsentWithOverdueTask = 'absent_with_overdue_task';

    public static function of(bool $absent, bool $overdue): ?self
    {
        return match (true) {
            $absent && $overdue => self::AbsentWithOverdueTask,
            $absent => self::ResponsibleAbsent,
            $overdue => self::OverdueTask,
            default => null,
        };
    }
}
