<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class PublicReportingUnavailableHttpException extends HttpException
{
    public function __construct()
    {
        parent::__construct(
            403,
            'Public reporting is unavailable in this fictional demonstration.',
        );
    }
}
