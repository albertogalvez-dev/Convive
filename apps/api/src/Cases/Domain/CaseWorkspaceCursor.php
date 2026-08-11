<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class CaseWorkspaceCursor
{
    public function __construct(
        public CaseOperationalView $view,
        public DateTimeImmutable $sortAt,
        public Uuid $caseId,
    ) {
    }
}
