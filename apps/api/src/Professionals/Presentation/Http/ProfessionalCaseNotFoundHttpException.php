<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ProfessionalCaseNotFoundHttpException extends NotFoundHttpException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('The case is unavailable.', previous: $previous);
    }
}
