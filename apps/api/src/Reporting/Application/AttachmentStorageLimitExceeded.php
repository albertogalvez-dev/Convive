<?php

declare(strict_types=1);

namespace App\Reporting\Application;

use RuntimeException;

final class AttachmentStorageLimitExceeded extends RuntimeException
{
}
