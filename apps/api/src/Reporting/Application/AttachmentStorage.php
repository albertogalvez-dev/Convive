<?php

declare(strict_types=1);

namespace App\Reporting\Application;

use App\Reporting\Domain\ReportAttachment;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * Private byte storage only. Implementations must never expose public URLs.
 */
interface AttachmentStorage
{
    public function storeQuarantine(Uuid $attachmentId, string $sourcePath): StoredAttachment;

    /** @return resource */
    public function open(ReportAttachment $attachment);

    public function promoteToAvailable(ReportAttachment $attachment): void;

    public function delete(ReportAttachment $attachment): void;

    public function deleteQuarantineObjectsOlderThan(DateTimeImmutable $deadline): int;
}
