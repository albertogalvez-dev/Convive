<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use InvalidArgumentException;

final readonly class CaseInvolvedPersonName
{
    public const int MAX_LENGTH = 120;

    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalised = preg_replace('/\s+/u', ' ', trim($value));

        if ($normalised === null || $normalised === '' || grapheme_strlen($normalised) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('An involved person name must contain between 1 and 120 characters.');
        }

        return new self($normalised);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
