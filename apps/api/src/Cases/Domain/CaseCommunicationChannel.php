<?php

declare(strict_types=1);

namespace App\Cases\Domain;

enum CaseCommunicationChannel: string
{
    case InPerson = 'in_person';
    case Telephone = 'telephone';
    case SecurePortal = 'secure_portal';
    case WrittenRecord = 'written_record';
    case Other = 'other';
}
