<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use DomainException;

final class ReportAlreadyReviewed extends DomainException
{
    public function __construct()
    {
        parent::__construct('The report has already been reviewed.');
    }
}
