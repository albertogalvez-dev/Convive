<?php

declare(strict_types=1);

namespace App\Reporting\Application;

use App\Reporting\Domain\AttachmentMediaType;
use InvalidArgumentException;

/**
 * A server-validated temporary upload. Client filenames and headers do not
 * cross this boundary.
 */
final readonly class QuarantinedAttachmentUpload
{
    public function __construct(
        public string $sourcePath,
        public AttachmentMediaType $mediaType,
    ) {
        if (!is_file($this->sourcePath) || !is_readable($this->sourcePath)) {
            throw new InvalidArgumentException('The attachment source is not readable.');
        }
    }
}
