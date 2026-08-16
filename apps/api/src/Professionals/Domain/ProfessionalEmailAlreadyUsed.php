<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

use RuntimeException;

final class ProfessionalEmailAlreadyUsed extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The professional email address is already in use.');
    }
}
