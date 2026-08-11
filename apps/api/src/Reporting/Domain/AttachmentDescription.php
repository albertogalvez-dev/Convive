<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use InvalidArgumentException;

/**
 * Reporter-supplied context for one attachment. This is deliberately separate
 * from the client filename, which never crosses the upload boundary.
 */
final readonly class AttachmentDescription
{
    public const MAX_LENGTH = 500;

    private function __construct(private string $value)
    {
    }

    public static function fromNullable(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        $normalised = preg_replace('/\A\s+|\s+\z/u', '', $value);

        if ($normalised === null) {
            throw new InvalidArgumentException('Attachment descriptions must contain valid UTF-8.');
        }

        if ($normalised === '') {
            return null;
        }

        if (mb_strlen($normalised, 'UTF-8') > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Attachment descriptions must not exceed %d characters.',
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
