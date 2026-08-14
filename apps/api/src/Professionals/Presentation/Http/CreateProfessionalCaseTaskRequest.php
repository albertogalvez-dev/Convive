<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateProfessionalCaseTaskRequest
{
    public function __construct(
        #[Assert\Uuid]
        public string $ownerId,
        #[Assert\Uuid]
        public string $templateId,
        #[Assert\Length(min: 1, max: 160)]
        public string $title,
        #[Assert\NotBlank]
        #[Assert\DateTime(format: 'Y-m-d\TH:i:sP')]
        public string $dueAt,
    ) {
    }
}
