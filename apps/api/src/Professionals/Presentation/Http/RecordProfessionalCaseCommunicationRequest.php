<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use Symfony\Component\Validator\Constraints as Assert;

#[\OpenApi\Attributes\Schema(additionalProperties: false)]
final readonly class RecordProfessionalCaseCommunicationRequest
{
    public function __construct(
        #[Assert\Uuid]
        public string $responsibleId,
        #[Assert\Choice(choices: ['family', 'external_service', 'education_inspectorate', 'other'])]
        public string $recipient,
        #[Assert\Choice(choices: ['in_person', 'telephone', 'secure_portal', 'written_record', 'other'])]
        public string $channel,
        #[Assert\Choice(choices: ['planned', 'recorded', 'not_applicable'])]
        public string $status,
        #[Assert\NotBlank]
        #[Assert\DateTime(format: 'Y-m-d\TH:i:sP')]
        public string $occurredAt,
        #[Assert\Length(min: 1, max: 500)]
        public string $note,
    ) {
    }
}
