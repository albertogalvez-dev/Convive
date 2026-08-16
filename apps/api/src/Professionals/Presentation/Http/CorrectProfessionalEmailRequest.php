<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Professionals\Domain\ProfessionalEmail;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CorrectProfessionalEmailRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: ProfessionalEmail::MAX_LENGTH)]
        public string $email,
    ) {
    }
}
