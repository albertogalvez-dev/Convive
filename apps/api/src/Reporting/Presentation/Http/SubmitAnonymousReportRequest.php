<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use App\Reporting\Domain\SituationDescription;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class SubmitAnonymousReportRequest
{
    public function __construct(
        #[Assert\NotBlank(
            message: 'Situation description must not be empty.',
            normalizer: 'trim',
        )]
        #[Assert\Length(
            max: SituationDescription::MAX_LENGTH,
            maxMessage: 'Situation description must not exceed 5000 characters.',
        )]
        public string $situationDescription,

        #[Assert\NotBlank(
            message: 'Situation context must not be empty.',
            normalizer: 'trim',
        )]
        #[Assert\Choice(
            choices: [
                'in_person',
                'digital',
                'mixed',
                'unknown',
            ],
            message: 'Situation context is not valid.',
        )]
        public string $situationContext,
    ) {
    }
}
