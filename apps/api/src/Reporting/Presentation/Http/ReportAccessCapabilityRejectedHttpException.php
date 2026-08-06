<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

final class ReportAccessCapabilityRejectedHttpException extends HttpException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            Response::HTTP_UNAUTHORIZED,
            'The report access capability was not accepted.',
            $previous,
        );
    }
}
