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
        public string $sourceId,
        #[Assert\Choice(choices: ['identification', 'immediate_actions', 'urgent_protection', 'family_communication', 'professional_coordination', 'information_collection', 'educational_measures', 'inspection_communication', 'assessment', 'action_plan', 'family_measures', 'inspection_follow_up'])]
        public string $stage,
        #[Assert\Choice(choices: ['internal_action', 'external_communication'])]
        public string $kind,
        #[Assert\Length(min: 1, max: 160)]
        public string $title,
        #[Assert\NotBlank]
        #[Assert\DateTime]
        public string $dueAt,
    ) {
    }
}
