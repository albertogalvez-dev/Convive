<?php

declare(strict_types=1);

namespace App\Cases\Domain;

enum CaseAssignmentRole: string
{
    case Lead = 'lead';
    case Contributor = 'contributor';
    case Observer = 'observer';
}
