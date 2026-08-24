<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use Symfony\Component\Validator\Constraints as Assert;

#[\OpenApi\Attributes\Schema(additionalProperties: false)]
final readonly class ChangeReportingChannelRequest
{
    public function __construct(
        #[Assert\Choice(choices: ['pause', 'activate', 'rotate', 'retire'])]
        public string $action,
    ) {
    }
}
