<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

final class InvalidFollowUpEntryHttpException extends HttpException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'The submitted follow-up entry is invalid.',
            $previous,
        );
    }
}
