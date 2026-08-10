<?php

declare(strict_types=1);

namespace App\Demo\Application;

final readonly class FictionalDemoSeedResult
{
    public function __construct(
        public int $organisations,
        public int $professionals,
        public int $reports,
        public int $conversationEntries,
        public bool $reset,
    ) {
    }
}
