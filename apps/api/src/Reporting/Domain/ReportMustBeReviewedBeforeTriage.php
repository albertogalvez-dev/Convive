<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use DomainException;

final class ReportMustBeReviewedBeforeTriage extends DomainException
{
    public function __construct()
    {
        parent::__construct('The report must receive its initial review before triage.');
    }
}
