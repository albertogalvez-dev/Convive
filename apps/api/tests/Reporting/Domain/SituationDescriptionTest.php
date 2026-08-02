<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Domain;

use App\Reporting\Domain\SituationDescription;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SituationDescriptionTest extends TestCase
{
    public function testItCreatesANormalisedDescription(): void
    {
        $description = SituationDescription::fromString(
            " \n A student is being excluded repeatedly. \t ",
        );

        self::assertSame(
            'A student is being excluded repeatedly.',
            $description->toString(),
        );
    }

    public function testItPreservesMeaningfulInternalWhitespace(): void
    {
        $description = SituationDescription::fromString(
            "First observation.\n\nSecond observation.",
        );

        self::assertSame(
            "First observation.\n\nSecond observation.",
            $description->toString(),
        );
    }

    public function testItAcceptsTheMaximumLength(): void
    {
        $value = str_repeat(
            'á',
            SituationDescription::MAX_LENGTH,
        );

        self::assertSame(
            $value,
            SituationDescription::fromString($value)->toString(),
        );
    }

    #[DataProvider('invalidDescriptions')]
    public function testItRejectsInvalidDescriptions(
        string $value,
        string $expectedMessage,
    ): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs($expectedMessage);

        SituationDescription::fromString($value);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidDescriptions(): iterable
    {
        yield 'empty' => [
            '',
            'Situation description must not be empty.',
        ];

        yield 'spaces only' => [
            '   ',
            'Situation description must not be empty.',
        ];

        yield 'whitespace only' => [
            " \n\t ",
            'Situation description must not be empty.',
        ];

        yield 'Unicode whitespace only' => [
            "\u{00A0}\u{00A0}",
            'Situation description must not be empty.',
        ];

        yield 'invalid UTF-8' => [
            "\xB1\x31",
            'Situation description must contain valid UTF-8.',
        ];

        yield 'too long' => [
            str_repeat(
                'a',
                SituationDescription::MAX_LENGTH + 1,
            ),
            sprintf(
                'Situation description must not exceed %d characters.',
                SituationDescription::MAX_LENGTH,
            ),
        ];
    }

    public function testItComparesDescriptionsByValue(): void
    {
        $description = SituationDescription::fromString(
            'A situation requiring professional assessment.',
        );

        self::assertTrue(
            $description->equals(
                SituationDescription::fromString(
                    'A situation requiring professional assessment.',
                ),
            ),
        );
        self::assertFalse(
            $description->equals(
                SituationDescription::fromString(
                    'A different situation.',
                ),
            ),
        );
    }
}
