<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure;

use App\Reporting\Application\AttachmentDownloadConcurrencyLimitReached;
use App\Reporting\Application\AttachmentDownloadConcurrencyLimiter;
use App\Reporting\Application\AttachmentDownloadPermit;
use App\Reporting\Domain\ReportAttachmentPolicy;
use RuntimeException;

/**
 * A small global permit pool shared through the private attachment volume.
 * The permit remains held for the exact lifetime of a streamed response.
 */
final class LocalPrivateAttachmentDownloadConcurrencyLimiter implements AttachmentDownloadConcurrencyLimiter
{
    private string $lockDirectory;

    public function __construct(LocalPrivateAttachmentStorage $storage)
    {
        $this->lockDirectory = $storage->privateDirectory().'/download-locks';

        if (!is_dir($this->lockDirectory) && !mkdir($this->lockDirectory, 0700, true) && !is_dir($this->lockDirectory)) {
            throw new RuntimeException('The private attachment download lock directory cannot be created.');
        }

        if (!chmod($this->lockDirectory, 0700)) {
            throw new RuntimeException('The private attachment download lock directory permissions cannot be set.');
        }
    }

    public function acquire(): AttachmentDownloadPermit
    {
        for ($slot = 1; $slot <= ReportAttachmentPolicy::MAXIMUM_CONCURRENT_DOWNLOADS; ++$slot) {
            $handle = @fopen($this->lockDirectory.'/slot-'.$slot.'.lock', 'c+');

            if ($handle === false) {
                throw new RuntimeException('A private attachment download lock cannot be opened.');
            }

            if (flock($handle, LOCK_EX | LOCK_NB)) {
                if (!chmod($this->lockDirectory.'/slot-'.$slot.'.lock', 0600)) {
                    flock($handle, LOCK_UN);
                    fclose($handle);

                    throw new RuntimeException('A private attachment download lock cannot be protected.');
                }

                return new FileAttachmentDownloadPermit($handle);
            }

            fclose($handle);
        }

        throw new AttachmentDownloadConcurrencyLimitReached();
    }
}
