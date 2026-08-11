<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure;

use Symfony\Component\Uid\Uuid;

final readonly class ReporterEmailDelivery
{
    public function __construct(
        public Uuid $id,
        public Uuid $contactId,
        public string $email,
        public string $kind,
        public int $attempt,
    ) {
    }
}
