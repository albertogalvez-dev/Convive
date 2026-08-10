<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure;

use App\Reporting\Application\AttachmentDownloadPermit;

final class FileAttachmentDownloadPermit implements AttachmentDownloadPermit
{
    /** @var resource|null */
    private $handle;

    /** @param resource $handle */
    public function __construct($handle)
    {
        $this->handle = $handle;
    }

    public function release(): void
    {
        if (!is_resource($this->handle)) {
            return;
        }

        flock($this->handle, LOCK_UN);
        fclose($this->handle);
        $this->handle = null;
    }

    public function __destruct()
    {
        $this->release();
    }
}
