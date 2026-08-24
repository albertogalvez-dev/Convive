<?php
declare(strict_types=1);
namespace App\Professionals\Presentation\Http;
use Symfony\Component\Validator\Constraints as Assert;
#[\OpenApi\Attributes\Schema(additionalProperties: false)]
final readonly class ManageCaseInvolvedPersonRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 120)]
        public string $name,
        #[Assert\Choice(choices: ['affected', 'alleged_actor', 'witness', 'guardian', 'other'])]
        public string $role,
    ) {}
}
