<?php

declare(strict_types=1);

namespace App\Cases\Application;

use App\Cases\Domain\CaseWorkspaceCursor;

final readonly class CaseWorkspacePage
{
    /**
     * @param list<CaseWorkspaceSummary> $items
     */
    public function __construct(
        public array $items,
        public ?CaseWorkspaceCursor $nextCursor,
    ) {
    }
}
