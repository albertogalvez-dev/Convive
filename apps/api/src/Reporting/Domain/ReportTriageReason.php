<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use InvalidArgumentException;

final readonly class ReportTriageReason
{
    public const MIN_LENGTH = 10;
    public const MAX_LENGTH = 1000;

    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $value = trim($value);
        $length = grapheme_strlen($value);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Report triage reason must contain between %d and %d characters.',
                self::MIN_LENGTH,
                self::MAX_LENGTH,
            ));
        }

        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
