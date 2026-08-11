<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class InvalidReporterEmailHttpException extends UnprocessableEntityHttpException
{
}
