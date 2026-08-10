<?php

declare(strict_types=1);

namespace App\Reporting\Application;

use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

final class AttachmentDownloadConcurrencyLimitReached extends TooManyRequestsHttpException
{
    public function __construct()
    {
        parent::__construct(1, 'The private attachment download capacity is temporarily full.');
    }
}
