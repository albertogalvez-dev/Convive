<?php

declare(strict_types=1);

namespace App\Reporting\Application;

use App\Reporting\Domain\ReportAttachmentPolicy;
use InvalidArgumentException;

final readonly class StoredAttachment
{
    public function __construct(
        public int $byteSize,
        public string $contentHash,
    ) {
        if ($this->byteSize < 1 || $this->byteSize > ReportAttachmentPolicy::MAXIMUM_FILE_BYTES) {
            throw new InvalidArgumentException('The stored attachment byte size is invalid.');
        }

        if (preg_match('/^[a-f0-9]{64}$/D', $this->contentHash) !== 1) {
            throw new InvalidArgumentException('The stored attachment content hash is invalid.');
        }
    }
}
