<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use RuntimeException;

final class CaseAccessDenied extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The case is not available in this professional scope.');
    }
}
