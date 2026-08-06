<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Domain;

use App\Reporting\Domain\ReportAccessCapability;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReportAccessCapabilityTest extends TestCase
{
    public function testItGeneratesACanonicalCapabilityHandle(): void
    {
        $capability = ReportAccessCapability::generate();
        $plainText = $capability->reveal();

        self::assertSame(
            ReportAccessCapability::LENGTH,
            strlen($plainText),
        );
        self::assertMatchesRegularExpression(
            '/\A[0-9a-f]{64}\z/D',
            $plainText,
        );
    }

    public function testItGeneratesDistinctHandlesOnEachCall(): void
    {
        $first = ReportAccessCapability::generate();
        $second = ReportAccessCapability::generate();

        self::assertNotSame($first->reveal(), $second->reveal());
    }

    public function testItParsesACanonicalHandle(): void
    {
        $value = str_repeat('b', ReportAccessCapability::LENGTH);

        $capability = ReportAccessCapability::fromString($value);

        self::assertSame($value, $capability->reveal());
    }

    public function testItDerivesTheExpectedLookupHash(): void
    {
        $capability = ReportAccessCapability::fromString(
            str_repeat('a', ReportAccessCapability::LENGTH),
        );

        self::assertSame(
            'ffe054fe7ae0cb6dc65c3af9b61d5209f439851db43d0ba5997337df154668eb',
            $capability->lookupHash(),
        );
    }

    #[DataProvider('invalidHandles')]
    public function testItRejectsAnInvalidHandle(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid report access capability handle.',
        );

        ReportAccessCapability::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidHandles(): iterable
    {
        yield 'empty value' => [''];

        yield 'too short' => [
            str_repeat('a', ReportAccessCapability::LENGTH - 1),
        ];

        yield 'too long' => [
            str_repeat('a', ReportAccessCapability::LENGTH + 1),
        ];

        yield 'uppercase hexadecimal' => [
            str_repeat('A', ReportAccessCapability::LENGTH),
        ];

        yield 'non-hexadecimal character' => [
            str_repeat('a', ReportAccessCapability::LENGTH - 1) . 'g',
        ];
    }
}
