<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

enum AttachmentMediaType: string
{
    case Pdf = 'application/pdf';
    case Jpeg = 'image/jpeg';
    case Png = 'image/png';

    public function extension(): string
    {
        return match ($this) {
            self::Pdf => 'pdf',
            self::Jpeg => 'jpg',
            self::Png => 'png',
        };
    }
}
