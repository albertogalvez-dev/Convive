<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

use InvalidArgumentException;

final readonly class ProfessionalEmail
{
    public const MAX_LENGTH = 255;

    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalizedValue = mb_strtolower(trim($value), 'UTF-8');

        if ($normalizedValue === '') {
            throw new InvalidArgumentException(
                'Professional email must not be empty.',
            );
        }

        if (mb_strlen($normalizedValue, 'UTF-8') > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'Professional email must not exceed %d characters.',
                    self::MAX_LENGTH,
                ),
            );
        }

        if (filter_var($normalizedValue, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException(
                'Professional email must be a valid email address.',
            );
        }

        return new self($normalizedValue);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
