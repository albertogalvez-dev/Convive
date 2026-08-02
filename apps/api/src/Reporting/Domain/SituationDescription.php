<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use InvalidArgumentException;

final readonly class SituationDescription
{
    public const MAX_LENGTH = 5000;

    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalizedValue = preg_replace(
            '/\A\s+|\s+\z/u',
            '',
            $value,
        );

        if ($normalizedValue === null) {
            throw new InvalidArgumentException(
                'Situation description must contain valid UTF-8.',
            );
        }

        if ($normalizedValue === '') {
            throw new InvalidArgumentException(
                'Situation description must not be empty.',
            );
        }

        if (mb_strlen($normalizedValue, 'UTF-8') > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'Situation description must not exceed %d characters.',
                    self::MAX_LENGTH,
                ),
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
