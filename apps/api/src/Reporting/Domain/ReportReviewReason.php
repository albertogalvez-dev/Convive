<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use InvalidArgumentException;

final readonly class ReportReviewReason
{
    public const MIN_LENGTH = 10;
    public const MAX_LENGTH = 1000;

    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalised = trim($value);
        $length = mb_strlen($normalised, 'UTF-8');

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Report review reason must contain between %d and %d characters.',
                self::MIN_LENGTH,
                self::MAX_LENGTH,
            ));
        }

        return new self($normalised);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
