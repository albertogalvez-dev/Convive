<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use DomainException;

final class ReportAttachmentQuotaExceeded extends DomainException
{
}
