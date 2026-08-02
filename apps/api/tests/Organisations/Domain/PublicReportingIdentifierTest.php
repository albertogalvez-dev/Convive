<?php

declare(strict_types=1);

namespace App\Tests\Organisations\Domain;

use App\Organisations\Domain\PublicReportingIdentifier;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PublicReportingIdentifierTest extends TestCase
{
    public function testItGeneratesACanonicalIdentifier(): void
    {
        $identifier = PublicReportingIdentifier::generate();

        self::assertSame(
            PublicReportingIdentifier::LENGTH,
            strlen($identifier->toString()),
        );
        self::assertMatchesRegularExpression(
            '/\AORG_[0-9A-HJKMNP-TV-Z]{16}\z/D',
            $identifier->toString(),
        );
    }

    public function testItParsesACanonicalIdentifier(): void
    {
        $identifier = PublicReportingIdentifier::fromString(
            'ORG_0123456789ABCDEF',
        );

        self::assertSame(
            'ORG_0123456789ABCDEF',
            $identifier->toString(),
        );
    }

    public function testItNormalisesLowercaseAndAmbiguousCharacters(): void
    {
        $identifier = PublicReportingIdentifier::fromString(
            'org_oiL23456789abcde',
        );

        self::assertSame(
            'ORG_01123456789ABCDE',
            $identifier->toString(),
        );
    }

    public function testItComparesIdentifiersByValue(): void
    {
        $identifier = PublicReportingIdentifier::fromString(
            'ORG_0123456789ABCDEF',
        );
        $sameIdentifier = PublicReportingIdentifier::fromString(
            'org_0123456789abcdef',
        );
        $differentIdentifier = PublicReportingIdentifier::fromString(
            'ORG_0123456789ABCDEG',
        );

        self::assertTrue($identifier->equals($sameIdentifier));
        self::assertFalse($identifier->equals($differentIdentifier));
    }

    #[DataProvider('invalidIdentifiers')]
    public function testItRejectsAnInvalidIdentifier(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid public reporting identifier.',
        );

        PublicReportingIdentifier::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidIdentifiers(): iterable
    {
        yield 'empty value' => [''];
        yield 'missing prefix' => ['0123456789ABCDEF'];
        yield 'wrong prefix' => ['REP_0123456789ABCDEF'];
        yield 'too short' => ['ORG_0123456789ABCDE'];
        yield 'too long' => ['ORG_0123456789ABCDEFG'];
        yield 'excluded letter u' => ['ORG_U123456789ABCDEF'];
        yield 'symbol' => ['ORG_0123456789ABCDE-'];
        yield 'whitespace' => ['ORG_0123456789ABCDE '];
        yield 'unicode homoglyph' => ['ORG_0123456789ABCDЕF'];
    }
}
