<?php

declare(strict_types=1);

namespace App\Tests\Professionals\Domain;

use App\Professionals\Domain\ProfessionalEmail;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ProfessionalEmailTest extends TestCase
{
    public function testItNormalizesCaseAndSurroundingWhitespace(): void
    {
        $email = ProfessionalEmail::fromString('  Alex.Rivera@IesValleSereno.example  ');

        self::assertSame(
            'alex.rivera@iesvallesereno.example',
            $email->toString(),
        );
    }

    public function testItRejectsAnEmptyValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProfessionalEmail::fromString('   ');
    }

    public function testItRejectsAnInvalidEmailAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProfessionalEmail::fromString('not-an-email');
    }

    public function testItRejectsAValueLongerThanTheMaximum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProfessionalEmail::fromString(
            str_repeat('a', ProfessionalEmail::MAX_LENGTH) . '@example.com',
        );
    }

    public function testEqualsComparesByNormalizedValue(): void
    {
        $first = ProfessionalEmail::fromString('Alex@Example.com');
        $second = ProfessionalEmail::fromString('alex@example.com');

        self::assertTrue($first->equals($second));
    }
}
