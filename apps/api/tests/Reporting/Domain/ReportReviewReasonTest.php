<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Domain;

use App\Reporting\Domain\ReportReviewReason;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReportReviewReasonTest extends TestCase
{
    public function testItNormalisesAValidReason(): void
    {
        self::assertSame(
            'Initial information assessed by the safeguarding team.',
            ReportReviewReason::fromString(
                '  Initial information assessed by the safeguarding team.  ',
            )->toString(),
        );
    }

    #[DataProvider('invalidReasons')]
    public function testItRejectsAnInvalidReason(string $reason): void
    {
        $this->expectException(InvalidArgumentException::class);

        ReportReviewReason::fromString($reason);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidReasons(): iterable
    {
        yield 'empty' => [''];
        yield 'too short' => ['Too short'];
        yield 'too long' => [str_repeat('a', ReportReviewReason::MAX_LENGTH + 1)];
    }
}
