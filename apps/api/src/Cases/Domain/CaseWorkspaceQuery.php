<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use DateTimeImmutable;

final readonly class CaseWorkspaceQuery
{
    public function __construct(
        public CaseOperationalView $view,
        public ?CaseStatus $status,
        public ?CaseModality $modality,
        public ?string $publicReference,
        public ?CaseWorkspaceCursor $cursor,
        public int $limit,
        public DateTimeImmutable $now,
    ) {
    }
}
