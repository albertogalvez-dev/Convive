<?php

declare(strict_types=1);

namespace App\Cases\Domain;

final class CaseAuditRetentionPolicy
{
    /**
     * This exists solely for the fictional demonstration lifecycle. A real
     * retention period requires controller/DPO approval and is deliberately
     * not configurable through this code path.
     */
    public const FICTIONAL_RETENTION = 'P30D';

    public const MAX_PURGE_BATCH = 200;
}
