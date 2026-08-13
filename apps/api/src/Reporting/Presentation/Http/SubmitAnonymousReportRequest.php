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

        #[Assert\Choice(
            choices: ['single', 'repeated', 'ongoing', 'unknown'],
            message: 'Reporter recurrence is not valid.',
        )]
        public string $reporterRecurrence = 'unknown',

        #[Assert\Choice(
            choices: ['needs_prompt_attention', 'no_prompt_attention_indicated', 'unknown'],
            message: 'Reporter attention cue is not valid.',
        )]
        public string $reporterAttentionCue = 'unknown',
    ) {
    }
}
