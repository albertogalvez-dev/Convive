<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use App\Reporting\Domain\SituationDescription;
use App\Reporting\Domain\ReportedPeople;
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

        #[Assert\Choice(
            choices: ['within_days', 'within_weeks', 'longer_ago', 'unknown'],
            message: 'Reporter timing is not valid.',
        )]
        public string $reporterTiming = 'unknown',

        // Optional in the strongest sense: absent and blank are the same
        // answer, and a report naming nobody is a complete report.
        #[Assert\Length(
            max: ReportedPeople::MAX_LENGTH,
            maxMessage: 'Reported people must not exceed {{ limit }} characters.',
        )]
        public ?string $reportedPeople = null,

        // Which entry point was used, not a question the reporter answered.
        // Defaults to first-person so an older client that does not send it
        // keeps producing exactly the reports it produces today.
        #[Assert\Choice(
            choices: ['experienced', 'witnessed'],
            message: 'Reporter perspective is not valid.',
        )]
        public string $reporterPerspective = 'experienced',
    ) {
    }
}
