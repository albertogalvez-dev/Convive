<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Domain;

use App\Reporting\Domain\ReportAccessSecret;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReportAccessSecretTest extends TestCase
{
    public function testItGeneratesACanonicalSecret(): void
    {
        $secret = ReportAccessSecret::generate();
        $plainText = $secret->reveal();

        self::assertSame(
            ReportAccessSecret::LENGTH,
            strlen($plainText),
        );
        self::assertMatchesRegularExpression(
            '/\A[0-9a-f]{64}\z/D',
            $plainText,
        );
    }

    public function testItParsesACanonicalSecret(): void
    {
        $value = str_repeat('a', ReportAccessSecret::LENGTH);

        $secret = ReportAccessSecret::fromString($value);

        self::assertSame($value, $secret->reveal());
    }

    public function testItDerivesTheExpectedLookupHash(): void
    {
        $secret = ReportAccessSecret::fromString(
            str_repeat('a', ReportAccessSecret::LENGTH),
        );

        self::assertSame(
            'ffe054fe7ae0cb6dc65c3af9b61d5209f439851db43d0ba5997337df154668eb',
            $secret->lookupHash(),
        );
    }

    #[DataProvider('invalidSecrets')]
    public function testItRejectsAnInvalidSecret(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid report access secret.',
        );

        ReportAccessSecret::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidSecrets(): iterable
    {
        yield 'empty value' => [''];

        yield 'too short' => [
            str_repeat('a', ReportAccessSecret::LENGTH - 1),
        ];

        yield 'too long' => [
            str_repeat('a', ReportAccessSecret::LENGTH + 1),
        ];

        yield 'uppercase hexadecimal' => [
            str_repeat('A', ReportAccessSecret::LENGTH),
        ];

        yield 'non-hexadecimal character' => [
            str_repeat('a', ReportAccessSecret::LENGTH - 1) . 'g',
        ];

        yield 'leading whitespace' => [
            ' ' . str_repeat('a', ReportAccessSecret::LENGTH - 1),
        ];

        yield 'trailing whitespace' => [
            str_repeat('a', ReportAccessSecret::LENGTH - 1) . ' ',
        ];

        yield 'unicode character' => [
            str_repeat('a', ReportAccessSecret::LENGTH - 1) . 'á',
        ];
    }
}
