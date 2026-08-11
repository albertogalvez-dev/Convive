<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Domain;

use App\Reporting\Domain\AttachmentDescription;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AttachmentDescriptionTest extends TestCase
{
    public function testItNormalisesOptionalReporterContext(): void
    {
        self::assertNull(AttachmentDescription::fromNullable(null));
        self::assertNull(AttachmentDescription::fromNullable(" \n\t "));
        self::assertSame(
            'Captura recibida por el informante.',
            AttachmentDescription::fromNullable('  Captura recibida por el informante.  ')?->toString(),
        );
    }

    public function testItRejectsInvalidOrOversizedReporterContext(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AttachmentDescription::fromNullable(str_repeat('a', AttachmentDescription::MAX_LENGTH + 1));
    }
}
