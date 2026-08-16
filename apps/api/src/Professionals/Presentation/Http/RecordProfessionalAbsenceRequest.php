<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Professionals\Domain\ProfessionalAbsence;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class RecordProfessionalAbsenceRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $startsOn,
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $endsOn,
        #[Assert\Length(max: ProfessionalAbsence::MAX_REASON_LENGTH)]
        public ?string $note = null,
    ) {
    }
}
