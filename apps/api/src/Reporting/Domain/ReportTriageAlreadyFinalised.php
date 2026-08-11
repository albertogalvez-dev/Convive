<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use DomainException;

final class ReportTriageAlreadyFinalised extends DomainException
{
    public function __construct()
    {
        parent::__construct('The report already has a terminal triage decision.');
    }
}
