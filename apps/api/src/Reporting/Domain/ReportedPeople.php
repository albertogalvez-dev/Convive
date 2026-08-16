<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use InvalidArgumentException;

/**
 * Whoever the reporter chose to name, in their own words.
 *
 * This is the most sensitive field a reporter can fill in: a minor may be
 * naming other minors. It is therefore optional in the strongest sense — an
 * absent value is not a missing answer, and a report that names nobody is
 * complete — and bounded to a short length, because the field is for "quién"
 * and not for a second account of what happened. Blank input becomes absence
 * rather than an empty string, so nothing downstream has to distinguish the
 * two.
 */
final readonly class ReportedPeople
{
    public const MAX_LENGTH = 200;

    private function __construct(private string $value)
    {
    }

    /** Returns null when the reporter left the field empty. */
    public static function fromNullableString(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Reported people must not exceed %d characters.', self::MAX_LENGTH),
            );
        }

        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
