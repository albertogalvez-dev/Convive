<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateProfessionalProfileRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: Professional::MAX_NAME_LENGTH)]
        public string $name,
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: ProfessionalEmail::MAX_LENGTH)]
        public string $email,
    ) {
    }
}
