<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Domain;

use App\Reporting\Domain\FollowUpEntryContent;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FollowUpEntryContentTest extends TestCase
{
    public function testItTrimsSurroundingWhitespace(): void
    {
        $content = FollowUpEntryContent::fromString(
            "  There is a new witness.  \n",
        );

        self::assertSame('There is a new witness.', $content->toString());
    }

    public function testItRejectsAnEmptyValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Follow-up content must not be empty.');

        FollowUpEntryContent::fromString('   ');
    }

    public function testItRejectsContentLongerThanTheMaximum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FollowUpEntryContent::fromString(
            str_repeat('a', FollowUpEntryContent::MAX_LENGTH + 1),
        );
    }

    public function testItAcceptsContentAtExactlyTheMaximum(): void
    {
        $value = str_repeat('a', FollowUpEntryContent::MAX_LENGTH);

        $content = FollowUpEntryContent::fromString($value);

        self::assertSame($value, $content->toString());
    }

    public function testEqualsComparesByValue(): void
    {
        $first = FollowUpEntryContent::fromString('Same content.');
        $second = FollowUpEntryContent::fromString('Same content.');

        self::assertTrue($first->equals($second));
    }
}
