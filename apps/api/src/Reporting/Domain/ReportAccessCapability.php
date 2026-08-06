<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use InvalidArgumentException;
use Random\RandomException;
use RuntimeException;
use SensitiveParameter;

final readonly class ReportAccessCapability
{
    public const LENGTH = 64;

    private const ENTROPY_BYTES = 32;
    private const LOOKUP_HASH_ALGORITHM = 'sha256';
    private const PATTERN = '/\A[0-9a-f]+\z/';

    private function __construct(
        #[SensitiveParameter]
        private string $plainText,
    ) {
        if (
            strlen($plainText) !== self::LENGTH
            || preg_match(self::PATTERN, $plainText) !== 1
        ) {
            throw new InvalidArgumentException(
                'Invalid report access capability handle.',
            );
        }
    }

    public static function generate(): self
    {
        try {
            return new self(
                bin2hex(random_bytes(self::ENTROPY_BYTES)),
            );
        } catch (RandomException $exception) {
            throw new RuntimeException(
                'Unable to generate a secure report access capability handle.',
                previous: $exception,
            );
        }
    }

    public static function fromString(
        #[SensitiveParameter]
        string $plainText,
    ): self {
        return new self($plainText);
    }

    public function reveal(): string
    {
        return $this->plainText;
    }

    public function lookupHash(): string
    {
        return hash(
            self::LOOKUP_HASH_ALGORITHM,
            $this->plainText,
        );
    }
}
