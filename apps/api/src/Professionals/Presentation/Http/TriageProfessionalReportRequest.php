<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TriageProfessionalReportRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['keep', 'redirect', 'dismiss', 'link_to_case'])]
        public string $outcome = '',
        #[Assert\Type('string')]
        public string $reason = '',
    ) {
    }
}
