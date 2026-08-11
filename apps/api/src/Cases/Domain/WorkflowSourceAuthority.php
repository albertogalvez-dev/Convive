<?php

declare(strict_types=1);

namespace App\Cases\Domain;

enum WorkflowSourceAuthority: string
{
    case Binding = 'binding';
    case Recommended = 'recommended';
    case Internal = 'internal';
}
