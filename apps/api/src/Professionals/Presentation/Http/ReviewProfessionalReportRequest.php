<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ReviewProfessionalReportRequest
{
    public function __construct(
        public string $reason,
        #[Assert\Choice(
            choices: ['peer_interaction', 'digital_interaction', 'exclusion_or_isolation', 'harmful_language_or_conduct', 'safety_or_wellbeing_concern', 'other', 'unknown'],
        )]
        public string $professionalConcernCategory = 'unknown',
        #[Assert\Choice(choices: ['single', 'repeated', 'ongoing', 'unknown'])]
        public string $professionalRecurrence = 'unknown',
        #[Assert\Choice(choices: ['needs_prompt_attention', 'no_prompt_attention_indicated', 'unknown'])]
        public string $professionalAttentionCue = 'unknown',
    )
    {
    }
}
