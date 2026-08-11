<?php

declare(strict_types=1);

namespace App\Cases\Domain;

enum CaseModality: string
{
    case InPerson = 'in_person';
    case Digital = 'digital';
    case Mixed = 'mixed';
    case Unknown = 'unknown';

}
