<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

final readonly class ReporterEmailAddress
{
    public const MAX_LENGTH = 254;

    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalised = mb_strtolower(trim($value));

        if (
            $normalised === ''
            || mb_strlen($normalised) > self::MAX_LENGTH
            || filter_var($normalised, FILTER_VALIDATE_EMAIL) === false
        ) {
            throw new \InvalidArgumentException('Reporter email must be a valid bounded address.');
        }

        return new self($normalised);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
