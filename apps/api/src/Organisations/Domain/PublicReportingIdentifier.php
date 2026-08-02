<?php

declare(strict_types=1);

namespace App\Organisations\Domain;

use InvalidArgumentException;

final readonly class PublicReportingIdentifier
{
    public const LENGTH = 20;

    private const PREFIX = 'ORG_';
    private const PAYLOAD_LENGTH = 16;
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    private const PAYLOAD_PATTERN = '/\A[0-9A-HJKMNP-TV-Z]{16}\z/D';

    private function __construct(private string $value)
    {
    }

    public static function generate(): self
    {
        $payload = '';

        for ($position = 0; $position < self::PAYLOAD_LENGTH; ++$position) {
            $payload .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return new self(self::PREFIX.$payload);
    }

    public static function fromString(string $value): self
    {
        if (strlen($value) !== self::LENGTH) {
            throw new InvalidArgumentException('Invalid public reporting identifier.');
        }

        $prefix = strtoupper(substr($value, 0, strlen(self::PREFIX)));

        if ($prefix !== self::PREFIX) {
            throw new InvalidArgumentException('Invalid public reporting identifier.');
        }

        $payload = strtoupper(substr($value, strlen(self::PREFIX)));
        $payload = strtr($payload, [
            'O' => '0',
            'I' => '1',
            'L' => '1',
        ]);

        if (preg_match(self::PAYLOAD_PATTERN, $payload) !== 1) {
            throw new InvalidArgumentException('Invalid public reporting identifier.');
        }

        return new self(self::PREFIX.$payload);
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
