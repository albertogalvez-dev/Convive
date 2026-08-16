<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class CaseWorkspaceQuery
{
    /**
     * Every filter narrows the caller's own assigned, organisation-scoped set.
     * None of them can widen it, so a filter can never reveal that a case the
     * caller cannot reach exists.
     */
    public function __construct(
        public CaseOperationalView $view,
        public ?CaseStatus $status,
        public ?CaseModality $modality,
        public ?string $publicReference,
        public ?Uuid $responsibleProfessionalId,
        public bool $onlyWithPendingTasks,
        public ?string $ownNoteText,
        public ?CaseWorkspaceCursor $cursor,
        public int $limit,
        public DateTimeImmutable $now,
    ) {
    }
}
